<?php

namespace App\Jobs;

use App\Benchmark\Utils;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Log;

class WorkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Time to spend on the task in milliseconds
     * @var int
     */
    public $runningTime = 1000;

    /**
     * Probability of clonning this task - reinsert back to the queue.
     * Simulates job insertions as well.
     * @var int
     */
    public $probabilityOfClone = 0;

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
     * @return void
     */
    public function handle()
    {
        if ($this->runningTime > 0) {
            usleep(max(floor($this->runningTime * 1000), 0));
        }

        // Clonning
        if ($this->probabilityOfClone > 0 && Utils::randomFloat() <= $this->probabilityOfClone){
            $job = new WorkJob();
            $job->runningTime = $this->runningTime;
            $job->probabilityOfClone = $this->probabilityOfClone;
            $job->onConnection($this->connection)
                ->onQueue($this->queue);

            try {
                app(Dispatcher::class)->dispatch($job);
            }catch(\Throwable $e){
                Log::error('INS probably deadlock: ' . $e->getMessage());
            }
        }
    }
}
