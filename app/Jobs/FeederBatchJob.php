<?php

namespace App\Jobs;

use App\Benchmark\Random\SystemRand;
use App\Benchmark\Utils;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeederBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
     * @throws \Exception
     */
    public function handle()
    {
        Log::info('Main feeding job started');
        Utils::deleteJobs();

        $rand = new SystemRand();
        $mean = config('benchmark.job_mean_time');
        $stddev = config('benchmark.job_stdev_time');
        $cloneProbab = config('benchmark.job_clone_probability');
        $batchSize = config('benchmark.job_batch_size');
        $workerQueue = config('benchmark.job_worker_queue');
        $workerConnection = config('benchmark.job_working_connection');

        $startJobId = Utils::getJobId();

        Log::info('Going to generate jobs: ' . $batchSize . ', start job id: ' . $startJobId);
        for($i=0; $i<$batchSize; $i++){
            $job = new WorkJob();
            $job->runningTime = $mean <= 0 ? -1 : $rand->gaussianRandom($mean, $stddev);
            $job->probabilityOfClone = $cloneProbab;
            $job->onConnection($workerConnection)
                ->onQueue($workerQueue)
                ->delay(10000000);
            dispatch($job);
        }

        // Schedule batch now
        Log::info('Kickoff all ' . $batchSize . ' jobs in 5 seconds');
        sleep(5);

        $startTime = microtime(true);
        DB::table(Utils::getJobTable())->update(['available_at' => 0]);

        // Monitor, query on count each 10 sec.
        $lastFetch = 0;
        while(true){
            $curTime = microtime(true);
            if ($curTime - $lastFetch < 0.25) {
                usleep(1000);
                continue;
            }

            $lastFetch = $curTime;
            $jobs = Utils::fetchNumJobs();
            Log::info('Number of jobs:' . $jobs);
            if ($jobs == 0){
                break;
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
