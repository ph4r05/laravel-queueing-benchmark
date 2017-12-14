<?php

namespace App\Benchmark\Queue;


use Illuminate\Queue\Connectors\DatabaseConnector;
use Illuminate\Support\Facades\Log;


class OptimisticDatabaseConnector extends DatabaseConnector
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new OptimisticDatabaseQueue(
            $this->connections->connection($config['connection'] ?? null),
            $config['table'],
            $config['queue'],
            $config['retry_after'] ?? 60,
            $config
        );
    }
}
