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
                                {--batch : batch job pre-generation}
                                {--batch-size= : batch size}
                                {--conn= : Override queue connection for workers, 0=pess, 1=opt, 2=beans}
                                {--del-tsx-fetch= : Fetch before delete}
                                {--del-tsx-retry= : Delete retry counts}
                                {--del-mark= : Delete mark 2stage}
                                {--work-clone= : Clonning probability}
                                {--work-mean= : Worker sleep mean time milliseconds}
                                {--window-strategy= : Optimistic windowing strategy}
                                {--verify= : Verify correctness by job counting, slower}
                                {--repeat=1 : Number of repeats for stats}
                                {--key=: key to identify the test}
                                {--no-json : Disable the json dump}';

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
        $conn = $this->option('conn');

        if ($conn === 0 || $conn === '0'){
            $job->conn = 'ph4DBPess';
        } elseif ($conn === 1 || $conn === '1'){
            $job->conn = 'ph4DBOptim';
        } elseif ($conn === 2 || $conn === '2'){
            $job->conn = 'beanstalkd';
        } elseif ($conn === 3 || $conn === '3'){
            $job->conn = 'redis';
        } else {
            $job->conn = $conn;
        }

        $job->batchSize = $this->option('batch-size');
        $job->delTsxFetch = $this->option('del-tsx-fetch');
        $job->delTsxRetry = $this->option('del-tsx-retry');
        $job->delMark = $this->option('del-mark');
        $job->workClone = $this->option('work-clone');
        $job->workMean = $this->option('work-mean');
        $job->windowStrategy = $this->option('window-strategy');
        $job->verify = $this->option('verify');
        $job->repeat = $this->option('repeat');
        $job->noJson = $this->option('no-json');
        $job->key = $this->option('key');

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
