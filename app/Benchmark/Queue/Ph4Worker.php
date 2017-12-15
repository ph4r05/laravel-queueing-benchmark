<?php
/**
 * Created by PhpStorm.
 * User: dusanklinec
 * Date: 23.05.17
 * Time: 13:02
 */

namespace App\Benchmark\Queue;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Log;


class Ph4Worker extends Worker
{
    public function __construct(QueueManager $manager, Dispatcher $events, ExceptionHandler $exceptions)
    {
        parent::__construct($manager, $events, $exceptions);
        Log::info('Worker created');
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
        Log::info($job->id);
        return parent::process($connectionName, $job, $options);
    }


}

