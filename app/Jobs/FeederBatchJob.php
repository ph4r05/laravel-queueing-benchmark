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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class FeederBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Override queue connection for workers
     */
    public $conn;

    /**
     * Fetch before delete
     */
    public $delTsxFetch;

    /**
     * Delete retry counts
     * @var
     */
    public $delTsxRetry;

    /**
     * Delete mark 2stage
     * @var
     */
    public $delMark;

    /**
     * Clonning probability
     * @var
     */
    public $workClone;

    /**
     * Worker sleep mean time milliseconds
     * @var
     */
    public $workMean;

    /**
     * Verify correctness
     * @var
     */
    public $verify;

    /**
     * @var boolean
     */
    protected $beans;

    protected $queueManager;
    protected $queueInstance;
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
        $stddev = floatval(config('benchmark.job_stdev_time'));
        $mean = floatval($this->workMean ?? config('benchmark.job_mean_time'));
        $cloneProbab = floatval($this->workClone ?? config('benchmark.job_clone_probability'));
        $deleteMark = filter_var($this->delMark ?? config('benchmark.job_delete_mark'), FILTER_VALIDATE_BOOLEAN);

        $batchSize = config('benchmark.job_batch_size');
        $workerQueue = config('benchmark.job_worker_queue');
        $this->workerConnection = $this->conn ?? config('benchmark.job_working_connection');

        $this->queueManager = $queueManager;
        $this->queueInstance = $queueManager->connection($this->workerConnection);
        $workerQueueInstance = $this->queueInstance;
        Log::info('Worker queue: ' . $workerQueueInstance->getConnectionName()
            . '; mark: ' . ($deleteMark ? 'Y' : 'N'));

        // Reconfigure dotenv
        $this->reconfigureDot();
        $this->reconfigure();
        $this->restartWorkers();

        $startJobId = Utils::getJobId();

        Log::info('Queue size: ' . $workerQueueInstance->size());
        Log::info('Going to generate jobs: ' . $batchSize
            . ', start job id: ' . $startJobId);

        for($i=0; $i<$batchSize; $i++){
            $job = new WorkJob();
            $job->runningTime = $mean <= 0 ? -1 : $rand->gaussianRandom($mean, $stddev);
            $job->probabilityOfClone = $cloneProbab;
            $job->onConnection($this->workerConnection)
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

    protected function restartWorkers(){
        Log::info('Restarting all queue workers...');
        Artisan::call('queue:restart');

        Log::info('Waiting for restart ...');
        sleep(10);
    }

    protected function reconfigureDot(){
        $settings = [];
        if ($this->delMark !== null){
            $settings[] = ['key' => 'B_JOB_DELETE_MARK', 'value' => $this->delMark];
        }
        if ($this->delTsxRetry !== null){
            $settings[] = ['key' => 'DELETE_TSX_RETRY', 'value' => $this->delTsxRetry];
        }
        if ($this->delTsxFetch !== null){
            $settings[] = ['key' => 'DELETE_TSX_FETCH', 'value' => $this->delTsxFetch];
        }
        if (!empty($settings)){
            DotenvEditor::setKeys($settings);
            DotenvEditor::save();
        }
    }

    protected function reconfigure(){
        $workerConnectionLow = strtolower($this->workerConnection);
        if (Str::contains($workerConnectionLow, ['beans'])){
            $this->beans = true;
            Log::info('Beanstalkd queueing');

        } elseif (Str::contains($workerConnectionLow, ['optim'])){
            $this->queueInstance->deleteFetch = filter_var($this->delTsxFetch ?? config('benchmark.db_delete_tsx'), FILTER_VALIDATE_BOOLEAN);
            Log::info('Optimistic queueing');

        } elseif (Str::contains($workerConnectionLow, ['pess'])) {
            $this->queueInstance->deleteFetch = filter_var($this->delTsxFetch ?? config('benchmark.db_delete_tsx'), FILTER_VALIDATE_BOOLEAN);
            $this->queueInstance->deleteMark = filter_var($this->delMark ?? config('benchmark.job_delete_mark'), FILTER_VALIDATE_BOOLEAN);
            $this->queueInstance->deleteRetry = intval($this->delTsxRetry ?? config('benchmark.db_delete_tsx_retry'));
            Log::info('Pessimistic queueing');

        } else {
            Log::info('Unknown queueing');

        }
    }
}
