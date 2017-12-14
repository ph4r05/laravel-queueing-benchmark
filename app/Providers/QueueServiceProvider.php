<?php

namespace App\Providers;

use App\Benchmark\Queue\PessimisticDatabaseConnector;
use App\Benchmark\Queue\Ph4Worker;
use App\Benchmark\Queue\OptimisticDatabaseConnector;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;


class QueueServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $manager = $this->app['queue'];
        $this->registerConnectors($manager);
        $this->registerWorker();
    }

    /**
     * Register the connectors on the queue manager.
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    public function registerConnectors($manager)
    {
        $this->registerPh4DatabaseConnector($manager);
    }

    /**
     * Register the database queue connector.
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerPh4DatabaseConnector($manager)
    {
        $manager->addConnector('ph4DBOptim', function () {
            return new OptimisticDatabaseConnector($this->app['db']);
        });

        $manager->addConnector('ph4DBPess', function () {
            return new PessimisticDatabaseConnector($this->app['db']);
        });
    }

    /**
     * Register the queue worker.
     *
     * @return void
     */
    protected function registerWorker()
    {
        $this->app->singleton('queue.worker', function () {
            return new Ph4Worker(
                $this->app['queue'], $this->app['events'], $this->app[ExceptionHandler::class]
            );
        });
    }
}
