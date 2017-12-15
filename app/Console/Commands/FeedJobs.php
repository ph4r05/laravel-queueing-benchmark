<?php

namespace App\Console\Commands;

use App\Benchmark\Random\SystemRand;
use App\Benchmark\Utils;
use App\Jobs\FeederBatchJob;
use App\Jobs\FeederJob;
use App\Jobs\WorkJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FeedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:feedJobs
                                {--cron : started by cron}
                                {--sync : synchronous processing}
                                {--batch : batch job pre-generation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Feeds new jobs to the queue';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->isBatch()){
            $this->startBatchJob();
        } else {
            $this->startFeederJob();
        }

        return 0;
    }

    /**
     * Generate batch, then kickoff.
     */
    protected function startBatchJob(){
        $job = new FeederBatchJob();
        $job->onConnection('sync')
            ->onQueue(null);
        dispatch($job);
    }

    /**
     * Self feeding job
     * More complicated logic.
     */
    protected function startFeederJob(){
        if ($this->isCron() && !config('benchmark.job_feeding')){
            return;
        }

        // Start the new ssh pool check job.
        $job = new FeederJob();

        if ($this->isSync()){
            $job->onConnection('sync')
                ->onQueue(null);

        } else {
            $job->onConnection(config('benchmark.job_feeding_connection'))
                ->onQueue(config('benchmark.job_feeder_queue'));
        }

        dispatch($job);
    }

    /**
     * Synchronous processing
     */
    protected function isSync(){
        return $this->option('sync');
    }

    /**
     * Batch processing
     */
    protected function isBatch(){
        return $this->option('batch');
    }

    /**
     * Started by cron
     */
    protected function isCron(){
        return $this->option('cron');
    }
}
