<?php

return [

    // Minimal size of the job size
    'job_pool_size' => env('B_JOB_POOL_SIZE', 20000),
    'job_batch_size' => env('B_JOB_BATCH_SIZE', 1000),
    'job_feeding' => env('B_FEEDING', false),
    'job_clone_probability' => env('B_JOB_CLONE', 0),

    // Number of spawned workers
    // Value used for work distribution. Does not influence number of started workers.
    'num_workers' => env('B_NUM_WORKERS', 50),

    // Number of feeder jobs to spawn from the main feeder job.
    'num_job_feeders' => env('B_NUM_JOB_FEEDERS', 10),
    'job_feeders_lifetime' => env('B_JOB_FEEDERS_LIFETIME', 30),

    // Connections used for a) work feeding b) working
    // May be separate or in the same work queue
    'job_feeding_connection' => env('B_JOB_FEEDER_CONN', 'database'),
    'job_working_connection' => env('B_JOB_WORKER_CONN', 'database'),

    // Default queueing settings.
    // Job feeders have to have a precedence before ordinary workers to make sure the queue is still full.
    // Moreover this mechanism will benchmark different queues performance.
    'job_feeder_queue' => env('B_JOB_FEEDER_QUEUE', 'high'),
    'job_sub_feeder_queue' => env('B_JOB_SUB_FEEDER_QUEUE', 'default'),
    'job_worker_queue' => env('B_WORKER_QUEUE', 'default'),

    // Mean time of one worker job. millisec.
    'job_mean_time' => env('B_JOB_MEAN_SIZE', 10),
    'job_stdev_time' => env('B_JOB_STDEV_SIZE', 1),

];
