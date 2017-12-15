<?php

namespace App\Jobs;

use App\Benchmark\Utils;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Class FeederJob
 * @deprecated too complicated for now
 * @package App\Jobs
 */
class FeederJob implements ShouldQueue
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
     */
    public function handle()
    {
        Log::info('Main feeding job started');

        // Get number of jobs in the DB
        $jobCount = Utils::getJobQuery()->whereNull('reserved_at')->count();
        $feeders = config('benchmark.num_job_feeders');

        for($i = 0; $i < $feeders; $i++){
            $job = new FeederSubJob();
            $job->feederIdx = $i;
            $job->jobsNum = $jobCount;

            $job->onConnection(config('benchmark.job_feeding_connection'))
                ->onQueue(config('benchmark.job_sub_feeder_queue'));

            dispatch($job);
        }
    }
}
