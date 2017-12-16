<?php

namespace App\Jobs;

use App\Benchmark\Random\SystemRand;
use App\Benchmark\Utils;
use App\Protocol;
use Illuminate\Foundation\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\Events\JobProcessing;
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
     * Batch size
     * @var
     */
    public $batchSize;

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
     * Optimistic window strategy
     * @var int
     */
    public $windowStrategy = 0;

    /**
     * Verify correctness
     * @var
     */
    public $verify;

    /**
     * @var boolean
     */
    protected $beans = false;

    /**
     * @var boolean
     */
    protected $optim = false;

    protected $numWorkers;
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

        $rand = new SystemRand();
        $stddev = floatval(config('benchmark.job_stdev_time'));
        $mean = floatval($this->workMean ?? config('benchmark.job_mean_time'));
        $cloneProbab = floatval($this->workClone ?? config('benchmark.job_clone_probability'));
        $deleteMark = filter_var($this->delMark ?? config('benchmark.job_delete_mark'), FILTER_VALIDATE_BOOLEAN);

        $batchSize = intval($this->batchSize ?? config('benchmark.job_batch_size'));
        $workerQueue = config('benchmark.job_worker_queue');
        $this->workerConnection = $this->conn ?? config('benchmark.job_working_connection');
        $this->numWorkers = intval(config('benchmark.num_workers'));
        $this->windowStrategy = intval($this->windowStrategy ?? intval(config('benchmark.optim_window_strategy')));
        $this->verify = Utils::bool($this->verify ?? intval(config('benchmark.verify_queueing')));

        $this->queueManager = $queueManager;
        $this->queueInstance = $queueManager->connection($this->workerConnection);
        $workerQueueInstance = $this->queueInstance;
        Log::info('Worker queue: ' . $workerQueueInstance->getConnectionName()
            . '; mark: ' . ($deleteMark ? 'Y' : 'N')
            . '; cloneP: ' . var_export($cloneProbab, true)
            . '; mean: ' . var_export($mean, true)
            . '; verify: ' . var_export($this->verify, true)
        );

        // Reconfigure dotenv
        $this->reconfigureDot();
        $this->reconfigure();

        Utils::deleteJobs($this->optim);
        $this->cleanProtocol();
        $this->restartWorkers();

        $startJobId = Utils::getJobId($this->optim);

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
            DB::table(Utils::getJobTable($this->optim))->update(['available_at' => 0]);
        }

        $startTime = microtime(true);

        // Monitor, query on count each 10 sec.
        $lastFetch = 0;
        while(true){
            $curTime = microtime(true);
            if ($curTime - $lastFetch < 0.5) {
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
                    DB::table(Utils::getJobTable($this->optim))->where('delete_mark', 1)->delete();
                } catch (\Throwable $e) {
                    // nah
                }
            }
        }

        $finalJobId = Utils::getJobId($this->optim) - 1;
        $numIds = $finalJobId - $startJobId;

        $finish = microtime(true);
        $elapsed = $finish - $startTime;
        $jobsPerSecond = $batchSize / floatval($elapsed);
        Log::info('Total running time: ' . $elapsed
            . ' it is ' . $jobsPerSecond
            . ' jobs per second, numIds: ' . $numIds);

        $this->verifyProtocol();
    }

    protected function verifyProtocol(){
        if (!$this->verify){
            return;
        }

        $proto = Protocol::query()->orderBy('id')->get()->pluck('jid')->values();
        $protoUnique = $proto->unique()->values();
        $startJobId = $proto->min();

        // Ordering analysis on proto.
        $len = $proto->count();
        $diffs = [];

        for($i=0; $i < $len; $i++){
            $diffs[] = abs($startJobId + $i - $proto[$i]);
        }

        $diffs = collect($diffs);
        Log::info('Diffs avg: ' . $diffs->avg()
            . ', min: ' . $diffs->min()
            . ', max: ' . $diffs->max()
            . ', median: ' . $diffs->median()
        );

        $counts = $diffs->groupBy(function($item, $key){
            return $item;
        })->map(function($item, $key){
            return [$key, count($item)];
        })->sortByDesc(function($item, $key){
            return $item;
        })->values();

        Log::info('Counts: ' . $counts->toJson());

        $topDiffs = $diffs->sort()->reverse()->take(40)->values();
        Log::info('Top difs: ' . $topDiffs->toJson());
        Log::info('Protocol entries: ' . $proto->count());
        Log::info('Protocol unique: ' . $protoUnique->count());
    }

    protected function cleanProtocol(){
        DB::table(Protocol::TABLE)->delete();
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
        if ($this->windowStrategy !== null){
            $settings[] = ['key' => 'B_OPTIM_WINDOW_STRATEGY', 'value' => $this->windowStrategy];
        }
        if ($this->verify !== null){
            $settings[] = ['key' => 'B_VERIFY_QUEUEING', 'value' => $this->verify];
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
            $this->optim = true;
            $this->queueInstance->deleteFetch = filter_var($this->delTsxFetch ?? config('benchmark.db_delete_tsx'), FILTER_VALIDATE_BOOLEAN);
            $this->queueInstance->numWorkers = $this->numWorkers;
            $this->queueInstance->windowStrategy = $this->windowStrategy;

            Log::info('Optimistic queueing '
                . ', deleteFetch: ' . var_export($this->queueInstance->deleteFetch, true)
                . ', numWorkers: ' . var_export($this->queueInstance->numWorkers, true)
                . ', windowStrategy: ' . var_export($this->queueInstance->windowStrategy, true)
            );

        } elseif (Str::contains($workerConnectionLow, ['pess'])) {
            $this->queueInstance->deleteFetch = filter_var($this->delTsxFetch ?? config('benchmark.db_delete_tsx'), FILTER_VALIDATE_BOOLEAN);
            $this->queueInstance->deleteMark = filter_var($this->delMark ?? config('benchmark.job_delete_mark'), FILTER_VALIDATE_BOOLEAN);
            $this->queueInstance->deleteRetry = intval($this->delTsxRetry ?? config('benchmark.db_delete_tsx_retry'));
            Log::info('Pessimistic queueing '
                . ', deleteFetch: ' . var_export($this->queueInstance->deleteFetch, true)
                . ', deleteMark: ' . var_export($this->queueInstance->deleteMark, true)
                . ', deleteRetry: ' . var_export($this->queueInstance->deleteRetry, true)
            );

        } else {
            Log::info('Unknown queueing');

        }
    }
}
