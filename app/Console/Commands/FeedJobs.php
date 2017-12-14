<?php

namespace App\Console\Commands;

use App\Jobs\FeederJob;
use Illuminate\Console\Command;

class FeedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:feedJobs
                                {--sync : synchronous processing}';

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
        return 0;
    }

    /**
     * Synchronous processing
     */
    protected function isSync(){
        return $this->option('sync');
    }
}
