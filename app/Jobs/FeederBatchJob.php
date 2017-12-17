<?php

namespace App\Jobs;

use App\Benchmark\Random\SystemRand;
use App\Benchmark\Utils;
use App\Protocol;
use Illuminate\Contracts\Queue\Queue;
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
use Illuminate\Support\Facades\Storage;
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
     * Repeat the test given number of times
     * @var
     */
    public $repeat;

    /**
     * No json log
     * @var
     */
    public $noJson;

    /**
     * @var boolean
     */
    protected $beans = false;

    /**
     * @var boolean
     */
    protected $optim = false;

    /**
     * @var QueueManager
     */
    protected $queueManager;
    protected $app;
    protected $runsDisk;

    /**
     * @var Queue
     */
    protected $queueInstance;
    protected $numWorkers;
    protected $workerQueue;
    protected $workerConnection;

    /**
     * @var SystemRand
     */
    protected $rand;
    protected $stddev;
    protected $mean;
    protected $cloneProbab;
    protected $deleteMark;

    protected $testResults;

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
     * @throws \Exception
     */
    public function handle(Application $app, QueueManager $queueManager)
    {
        Log::info('Main feeding job started');
        $this->app = $app;
        $this->queueManager = $queueManager;
        $this->initSettings();
        $timeStart = time();

        Log::info('Worker queue: ' . $this->queueInstance->getConnectionName()
            . '; mark: ' . ($this->deleteMark ? 'Y' : 'N')
            . '; cloneP: ' . var_export($this->cloneProbab, true)
            . '; mean: ' . var_export($this->mean, true)
            . '; verify: ' . var_export($this->verify, true)
        );

        // Reconfigure dotenv
        $this->reconfigureDot();
        $this->reconfigure();

        Utils::deleteJobs($this->optim);
        $this->cleanProtocol();

        $this->restartWorkers();

        // The test
        $jpsAvg = 0.0;
        for($i=0; $i < $this->repeat; $i++){
            Log::info('------------------ Test run ' . ($i+1) . '/' . $this->repeat);

            Utils::deleteJobs($this->optim);
            $this->cleanProtocol();

            $res = $this->benchmark();
            $jpsAvg += $res['jps'];
            $this->testResults[] = $res;
        }

        $jpsAvg /= $this->repeat;
        Log::info('Tests finished, average jobs per second: ' . $jpsAvg);
        if (Utils::bool($this->noJson)){
            return;
        }

        $fname = sprintf('run_%s_%s_conn%d_dm%d_dtsx%d_dretry%d_batch%d_cl%s_window%d_verify%d.json',
            time(),
            env('DB_CONNECTION'),
            $this->connIdx(),
            $this->deleteMark,
            $this->delTsxFetch,
            $this->delTsxRetry,
            $this->batchSize,
            $this->cloneProbab,
            $this->windowStrategy,
            $this->verify
        );

        $this->runsDisk->put($fname, json_encode([
            'timeStart' => $timeStart,
            'settings' => [
                'batchSize' => $this->batchSize,
                'db_conn' => env('DB_CONNECTION'),
                'conn' => $this->conn,
                'delTsxFetch' => $this->delTsxFetch,
                'delTsxRetry' => $this->delTsxRetry,
                'delMark' => $this->delMark,
                'deleteMark' => $this->deleteMark,
                'workClone' => $this->workClone,
                'workMean' => $this->workMean,
                'windowStrategy' => $this->windowStrategy,
                'verify' => $this->verify,
                'repeat' => $this->repeat,
                'beans' => $this->beans,
                'optim' => $this->optim,
                'numWorkers' => $this->numWorkers,
                'workerQueue' => $this->workerQueue,
                'workerConnection' => $this->workerConnection,
                'stddev' => $this->stddev,
                'mean' => $this->mean,
                'cloneProbab' => $this->cloneProbab,
            ],
            'runs' => $this->testResults,
            'jps_avg' => $jpsAvg,
        ], JSON_PRETTY_PRINT));
    }

    /**
     * Connection code
     * @return mixed
     */
    protected function connIdx(){
        if (Str::contains($this->conn, ['Pess'])){
            return 0;
        } elseif (Str::contains($this->conn, ['Opt'])){
            return 1;
        } else if (Str::contains($this->conn, 'beans')){
            return 2;
        } else if (Str::contains($this->conn, 'redis')){
            return 3;
        } else {
            return $this->conn;
        }
    }

    /**
     * Initialize input settings
     * Reads job input parameters, env file.
     */
    protected function initSettings(){
        $this->runsDisk = Storage::disk('runs');

        $this->rand = new SystemRand();
        $this->stddev = floatval(config('benchmark.job_stdev_time'));
        $this->mean = floatval($this->workMean ?? config('benchmark.job_mean_time'));
        $this->cloneProbab = floatval($this->workClone ?? config('benchmark.job_clone_probability'));
        $this->deleteMark = Utils::bool($this->delMark ?? config('benchmark.job_delete_mark'));
        $this->delTsxFetch = Utils::bool($this->delTsxFetch ?? config('benchmark.db_delete_tsx'));
        $this->delTsxRetry = intval($this->delTsxRetry ?? config('benchmark.db_delete_tsx_retry'));

        $this->batchSize = intval($this->batchSize ?? config('benchmark.job_batch_size'));
        $this->workerQueue = config('benchmark.job_worker_queue');
        $this->workerConnection = $this->conn ?? config('benchmark.job_working_connection');
        $this->numWorkers = intval(config('benchmark.num_workers'));
        $this->windowStrategy = intval($this->windowStrategy ?? intval(config('benchmark.optim_window_strategy')));
        $this->verify = Utils::bool($this->verify ?? intval(config('benchmark.verify_queueing')));
        $this->repeat = intval($this->repeat) ?? 1;

        $this->queueInstance = $this->queueManager->connection($this->workerConnection);
        $this->testResults = [];
    }

    /**
     * @throws \Exception
     */
    protected function benchmark(){
        $startJobId = Utils::getJobId($this->optim);

        Log::info('Queue size: ' . $this->queueInstance->size());
        Log::info('Going to generate jobs: ' . $this->batchSize
            . ', start job id: ' . $startJobId);

        for($i=0; $i<$this->batchSize; $i++){
            $job = new WorkJob();
            $job->runningTime = $this->mean <= 0 ? -1 : $this->rand->gaussianRandom($this->mean, $this->stddev);
            $job->probabilityOfClone = $this->cloneProbab;
            $job->onConnection($this->workerConnection)
                ->onQueue($this->workerQueue);

            if (!$this->beans){
                $job->delay(10000000);
            }
            dispatch($job);
        }

        // Schedule batch now
        if (!$this->beans) {
            Log::info('Kickoff all ' . $this->batchSize . ' jobs in 3 seconds');
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
            $jobs = $this->queueInstance->size();
            Log::info('Number of jobs:' . $jobs);
            if ($jobs == 0){
                break;
            }
        }

        $finalJobId = Utils::getJobId($this->optim) - 1;
        $numIds = $finalJobId - $startJobId;

        $finish = microtime(true);
        $elapsed = $finish - $startTime;
        $jobsPerSecond = $this->batchSize / floatval($elapsed);
        Log::info('Total running time: ' . $elapsed
            . ' it is ' . $jobsPerSecond
            . ' jobs per second, numIds: ' . $numIds);

        $runResults = [
            'elapsed' => $elapsed,
            'jps' => $jobsPerSecond,
            'numIds' => $numIds
        ];

        $runResults = $this->verifyProtocol($runResults);
        return $runResults;
    }

    /**
     * Verification phase
     * Checks the run protocol and computes stats about the run.
     * @param $runResults
     * @return
     */
    protected function verifyProtocol($runResults){
        if (!$this->verify){
            return $runResults;
        }

        $proto = Protocol::query()->orderBy('id')->get();
        $workload = $proto->groupBy(function($item, $key){
            return $item->pid;
        })->map(function($item, $key){
            return [$key, count($item)];
        })->sortByDesc(function($item, $key){
            return $item[1];
        })->values();

        $proto = $proto->pluck('jid')->values();
        $protoUnique = $proto->unique()->values();
        $startJobId = $proto->min();

        // Ordering analysis on proto.
        $len = $proto->count();
        $diffs = [];

        for($i=0; $i < $len; $i++){
            $diffs[] = abs($startJobId + $i - $proto[$i]);
        }

        $diffs = collect($diffs);

        $counts = $diffs->groupBy(function($item, $key){
            return $item;
        })->map(function($item, $key){
            return [$key, count($item)];
        })->sortBy(function($item, $key){
            return $item[0];
        })->values();

        $topDiffs = $diffs->sort()->reverse()->take(40)->values();

        $duplicities = $proto->groupBy(function($item, $key){
            return $item;
        })->map(function($item, $key){
            return count($item);
        })->reject(function($item, $key){
            return $item <= 1;
        })->values()->groupBy(function($item, $key){
            return $item;
        })->map(function($item, $key){
            return [$key, count($item)];
        })->sortBy(function($item, $key){
            return $item[0];
        })->values();

        $runResults['diffs'] = [
            'min' => $diffs->min(),
            'avg' => $diffs->avg(),
            'max' => $diffs->max(),
            'med' => $diffs->median(),
        ];
        $runResults['counts'] = $counts;
        $runResults['topDiffs'] = $topDiffs;
        $runResults['protoEntries'] = $proto->count();
        $runResults['protoUnique'] = $protoUnique->count();
        $runResults['duplicities'] = $duplicities;
        $runResults['workload'] = $workload;

        Log::info('Diffs avg: ' . $runResults['diffs']['avg']
            . ', min: ' . $runResults['diffs']['min']
            . ', max: ' . $runResults['diffs']['max']
            . ', median: ' . $runResults['diffs']['med']
        );
        Log::info('Counts: ' . $counts->toJson());
        Log::info('Top difs: ' . $topDiffs->toJson());
        Log::info('Protocol entries: ' . $proto->count());
        Log::info('Protocol unique: ' . $protoUnique->count());
        Log::info('Duplicity run: ' . $duplicities->toJson());
        Log::info('Workload distribution: ' . $workload->toJson());

        return $runResults;
    }

    /**
     * Empties protocol table.
     */
    protected function cleanProtocol(){
        DB::table(Protocol::TABLE)->delete();
    }

    /**
     * Simple worker restart with 10s sleep
     */
    protected function restartWorkers(){
        Log::info('Restarting all queue workers...');
        Artisan::call('queue:restart');

        Log::info('Waiting for restart ...');
        sleep(10);
    }

    /**
     * Configure .env from the current settings.
     * The point: Affects also the worker threads after the restart.
     */
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

    /**
     * Queue reconfiguration
     */
    protected function reconfigure(){
        $workerConnectionLow = strtolower($this->workerConnection);
        if (Str::contains($workerConnectionLow, ['beans'])){
            $this->beans = true;
            Log::info('Beanstalkd queueing');

        } elseif (Str::contains($workerConnectionLow, ['optim'])){
            $this->optim = true;
            $this->queueInstance->deleteFetch = $this->delTsxFetch;
            $this->queueInstance->numWorkers = $this->numWorkers;
            $this->queueInstance->windowStrategy = $this->windowStrategy;

            Log::info('Optimistic queueing '
                . ', deleteFetch: ' . var_export($this->queueInstance->deleteFetch, true)
                . ', numWorkers: ' . var_export($this->queueInstance->numWorkers, true)
                . ', windowStrategy: ' . var_export($this->queueInstance->windowStrategy, true)
            );

        } elseif (Str::contains($workerConnectionLow, ['pess'])) {
            $this->queueInstance->deleteFetch = $this->delTsxFetch;
            $this->queueInstance->deleteMark = $this->deleteMark;
            $this->queueInstance->deleteRetry = $this->delTsxRetry;
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
