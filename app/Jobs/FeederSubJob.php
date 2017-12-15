<?php

namespace App\Jobs;

use App\Benchmark\Utils;
use App\Benchmark\Random\SystemRand;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Illuminate\Support\Facades\Log;
use MathPHP\Statistics\RandomVariable;
use MathPHP\Probability\Distribution\Continuous\Normal;

/**
 * Class FeederSubJob
 * @deprecated too complicated for now
 * @package App\Jobs
 */
class FeederSubJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of active jobs in the queue
     *
     * @var int
     */
    public $jobsNum;

    /**
     * Feeder index - this process.
     *
     * @var int
     */
    public $feederIdx;

    protected $mean;
    protected $stddev;
    protected $rand;

    protected $workerQueue;
    protected $workerConnection;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \App\Benchmark\Random\RandException
     */
    public function handle()
    {
        Log::info('Subfeeder ' . $this->feederIdx . ' started');

        $this->rand = new SystemRand();
        $this->mean = config('benchmark.job_mean_time');
        $this->stddev = config('benchmark.job_stdev_time');
        $this->workerQueue = config('benchmark.job_worker_queue');
        $this->workerConnection = config('benchmark.job_working_connection');

        $poolSize = config('benchmark.job_pool_size');
        $numFeeders = config('benchmark.num_job_feeders');
        $numWorkers = config('benchmark.num_workers');
        $lifetime = config('benchmark.job_feeders_lifetime');

        $timeStart = microtime(true);

        $jobsPerSecond = $numWorkers * (1000 / $this->mean);
        $generateJobsPerSecond = $jobsPerSecond / $numFeeders;

        $jobMultiplier = 1.5; // estimate multiplier - generate mult more than estimated consumption
        $estimatorFrequency = 5 * 1000;
        $feederCycle = 100; // 100 milliseconds

        $nextJobEstimateTime = $timeStart + $estimatorFrequency + $this->rand->gaussianRandom(100000, 1000);
        $lastJobEstimate = 0;
        $defJobsToGenerate = 0; // deficient jobs

        // Work loop
        while(true) {
            // Generate jobs each x milliseconds.
            usleep($feederCycle * 1000);

            $curTime = microtime(true);
            $timeLeft = $lifetime - ($curTime - $timeStart);
            Log::info($this->feederIdx.':TimeLeft: ' . $timeLeft);

            if ($timeLeft < 0){
                break;
            }

            // Generate $defJobsToGenerate jobs if any.
            // Generate number of jobs per time unit from estimated consumption
            $generateNow = floor($jobMultiplier * $generateJobsPerSecond / (1000.0 / $feederCycle));
            Log::info($this->feederIdx.': generate now: ' . $generateNow);

            for($i = 0; $i < $defJobsToGenerate + $generateNow; $i++){
                dispatch($this->generateNextJob());
            }

            $defJobsToGenerate = 0;

            // Reestimate number of missing jobs in the pool
            if ($curTime > $nextJobEstimateTime){
                $nextJobEstimateTime = $curTime + $estimatorFrequency + $this->rand->gaussianRandom(100000, 1000);
                $lastJobEstimate = $this->getNumJobs();
                $defJobsToGenerate = ceil(max(0, (1.5 * $poolSize - $lastJobEstimate) / $numFeeders));
                $jobMultiplier = $defJobsToGenerate > 0 ? 1.5 : 0.5;

                Log::info($this->feederIdx.' reestimate, num: ' . $lastJobEstimate
                    . ' toGen: ' . $defJobsToGenerate);
            }
        }
    }

    protected function getNumJobs(){
        return Utils::getJobQuery()->count();
    }

    protected function generateNextJob(){
        $job = new WorkJob();
        $job->runningTime = $this->rand->gaussianRandom($this->mean, $this->stddev);
        $job->onConnection($this->workerConnection)
            ->onQueue($this->workerQueue);
        return $job;
    }
}
