<?php

namespace App\Jobs;

use App\Benchmark\Random\SystemRand;
use App\Benchmark\Utils;
use Illuminate\Foundation\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FeederBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var boolean
     */
    public $beans;

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
     * @param Application $app
     * @param QueueManager $queueManager
     * @return void
     * @throws \App\Benchmark\Random\RandException
     * @throws \Exception
     */
    public function handle(Application $app, QueueManager $queueManager)
    {
        Log::info('Main feeding job started');
        Utils::deleteJobs();

        $rand = new SystemRand();
        $mean = config('benchmark.job_mean_time');
        $stddev = config('benchmark.job_stdev_time');
        $cloneProbab = config('benchmark.job_clone_probability');
        $deleteMark = config('benchmark.job_delete_mark');
        $batchSize = config('benchmark.job_batch_size');
        $workerQueue = config('benchmark.job_worker_queue');
        $workerConnection = config('benchmark.job_working_connection');

        $workerQueueInstance = $queueManager->connection($workerConnection);
        Log::info('Worker queue: ' . $workerQueueInstance->getConnectionName());

        if (Str::contains($workerConnection, ['beans'])){
            $this->beans = true;
            Log::info('Beanstalkd queueing');
        }

        $startJobId = Utils::getJobId();

        Log::info('Queue size: ' . $workerQueueInstance->size());
        Log::info('Going to generate jobs: ' . $batchSize . ', start job id: ' . $startJobId);
        for($i=0; $i<$batchSize; $i++){
            $job = new WorkJob();
            $job->runningTime = $mean <= 0 ? -1 : $rand->gaussianRandom($mean, $stddev);
            $job->probabilityOfClone = $cloneProbab;
            $job->onConnection($workerConnection)
                ->onQueue($workerQueue);

            if (!$this->beans){
                $job->delay(10000000);
            }
            dispatch($job);
        }

        // Schedule batch now
        if (!$this->beans) {
            Log::info('Kickoff all ' . $batchSize . ' jobs in 3 seconds');
            sleep(3);
            DB::table(Utils::getJobTable())->update(['available_at' => 0]);
        }

        $startTime = microtime(true);

        // Monitor, query on count each 10 sec.
        $lastFetch = 0;
        while(true){
            $curTime = microtime(true);
            if ($curTime - $lastFetch < 0.25) {
                usleep(1000);
                continue;
            }

            $lastFetch = $curTime;
            $jobs = $workerQueueInstance->size();
            Log::info('Number of jobs:' . $jobs);
            if ($jobs == 0){
                break;
            }

            if (!$this->beans && $deleteMark) {
                try {
                    DB::table(Utils::getJobTable())->where('delete_mark', 1)->delete();
                } catch (\Throwable $e) {
                    // nah
                }
            }
        }

        $finalJobId = Utils::getJobId() - 1;
        $numIds = $finalJobId - $startJobId;

        $finish = microtime(true);
        $elapsed = $finish - $startTime;
        $jobsPerSecond = $batchSize / floatval($elapsed);
        Log::info('Total running time: ' . $elapsed
            . ' it is ' . $jobsPerSecond
            . ' jobs per second, numIds: ' . $numIds);
    }
}
