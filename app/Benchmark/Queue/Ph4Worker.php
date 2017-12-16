<?php
/**
 * Created by PhpStorm.
 * User: dusanklinec
 * Date: 23.05.17
 * Time: 13:02
 */

namespace App\Benchmark\Queue;

use App\Benchmark\Utils;
use App\Protocol;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Log;


class Ph4Worker extends Worker
{
    /**
     * Verify correctness - protocol the run
     * @var bool
     */
    protected $verify = false;

    public function __construct(QueueManager $manager, Dispatcher $events, ExceptionHandler $exceptions)
    {
        parent::__construct($manager, $events, $exceptions);

        $this->verify = Utils::bool(config('benchmark.verify_queueing'));
        Log::info('Worker created ' . getmypid() . ', verify: ' . var_export($this->verify, true));
    }

    /**
     * @param WorkerOptions $options
     */
    public function fixSleepOptions(WorkerOptions $options){
        $options->sleep = floatval($options->sleep);
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param  int   $seconds
     * @return void
     */
    public function sleep($seconds)
    {
        usleep(floatval($seconds) * 1e6);
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param  int   $microseconds
     * @return void
     */
    public function usleep($microseconds)
    {
        usleep($microseconds);
    }

    /**
     * @param string $connectionName
     * @param \Illuminate\Contracts\Queue\Job $job
     * @param WorkerOptions $options
     * @throws \Throwable
     */
    public function process($connectionName, $job, WorkerOptions $options)
    {
        // Verify protocol.
        // We could also capture \Events\JobProcessing
        if ($this->verify && method_exists($job, 'getJobId')){
            $proto = new Protocol([
                'tstamp' => ceil(microtime(true) * 100000),
                'pid' => getmypid(),
                'jid' => $job->getJobId(),
            ]);
            $proto->save();
        }

        return parent::process($connectionName, $job, $options);
    }

}

