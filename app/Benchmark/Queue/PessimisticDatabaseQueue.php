<?php

namespace App\Benchmark\Queue;

use Illuminate\Database\Connection;
use Illuminate\Database\DetectsDeadlocks;
use Illuminate\Database\Query\Expression;
use Illuminate\Queue\DatabaseQueue;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Queue\Jobs\DatabaseJobRecord;
use Illuminate\Support\Facades\Log;

class PessimisticDatabaseQueue extends DatabaseQueue implements QueueContract
{
    use DetectsDeadlocks;

    /**
     * Should perform transaction(find; delete by id)
     * @var bool
     */
    public $deleteFetch = true;

    /**
     * Retry indicator for delete tsx
     * @var int
     */
    public $deleteRetry = 0;

    /**
     * Delete mark - 2 stage delete
     * @var bool
     */
    public $deleteMark = false;

    /**
     * Create a new database queue instance.
     *
     * @param Connection $database
     * @param  string $table
     * @param  string $default
     * @param  int $retryAfter
     * @param array $config
     */
    public function __construct(Connection $database, string $table, string $default = 'default', int $retryAfter = 60, $config = [])
    {
        parent::__construct($database, $table, $default, $retryAfter);
        $this->deleteFetch = $config['deleteFetch'] ?? config('benchmark.db_delete_tsx');
        $this->deleteRetry = $config['deleteRetry'] ?? config('benchmark.db_delete_tsx_retry');
        $this->deleteMark = $config['deleteMark'] ?? config('benchmark.job_delete_mark');
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     * @throws \Exception|\Throwable
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        do {
            $job = $this->database->transaction(function () use ($queue) {

            if ($job = $this->getNextAvailableJob($queue)) {

                    if ($this->deleteMark && $job->delete_mark) {
                    Log::info('...delete mark: ' . $job->id);
                        $job = new DatabaseJob($this->container, $this, $job, $this->connectionName, $queue);
                        $job->delete();
                        return -1; // will trigger another load

                    } else {
                return $this->marshalJob($queue, $job);
            }
                }

            return null;
        });
        } while($this->deleteMark && $job === -1);
        return $job;
    }

    /**
     * Marshal the reserved job into a DatabaseJob instance.
     *
     * @param  string  $queue
     * @param  \Illuminate\Queue\Jobs\DatabaseJobRecord  $job
     * @return \Illuminate\Queue\Jobs\DatabaseJob
     */
    protected function marshalJob($queue, $job)
    {
        $job = $this->markJobAsReserved($job);
        return new DatabaseJob(
            $this->container, $this, $job, $this->connectionName, $queue
        );
    }

    /**
     * Delete a reserved job from the queue.
     * https://github.com/laravel/framework/issues/7046
     *
     * @param  string $queue
     * @param  string $id
     * @return void
     * @throws \Exception|\Throwable
     */
    public function deleteReserved($queue, $id)
    {
        if($this->deleteMark) {
            $this->database->transaction(function () use ($queue, $id) {
                $this->database->table($this->table)->where('id', $id)->update(['delete_mark' => 1]);
            });
        }

        try {
            if ($this->deleteFetch) {
                $this->database->transaction(function () use ($queue, $id) {
                    if ($this->database->table($this->table)->lockForUpdate()->find($id)) {
                        $this->database->table($this->table)->where('id', $id)->delete();
                    }
                }, $this->deleteRetry > 0 ? $this->deleteRetry : 1);

            } else {

                if ($this->deleteRetry <= 0) {
                    $this->database->table($this->table)->where('id', $id)->delete();

                } else {
                    $this->database->transaction(function () use ($queue, $id) {
                        $this->database->table($this->table)->where('id', $id)->delete();
                    }, $this->deleteRetry);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Probably deadlock: ' . $e->getMessage());
        }
    }

}
