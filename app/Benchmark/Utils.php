<?php
/**
 * Created by PhpStorm.
 * User: dusanklinec
 * Date: 14.12.17
 * Time: 22:32
 */

namespace App\Benchmark;

use App\Job;
use App\JobOptim;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Utils
{
    /**
     * @return bool
     */
    public static function isJobOptim(){
        return config('benchmark.job_working_connection') == 'ph4DBOptim';
    }

    /**
     * @return string
     */
    public static function getJobModel(){
        return self::isJobOptim() ? JobOptim::class : Job::class;
    }

    /**
     * @return string
     */
    public static function getJobTable(){
        return self::isJobOptim() ? JobOptim::TABLE : Job::TABLE;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getJobQuery(){
        return self::isJobOptim() ? JobOptim::query() : Job::query();
    }

    /**
     * Delete all jobs
     */
    public static function deleteJobs(){
        return DB::table(self::getJobTable())->delete();
    }

    /**
     * Number of jobs in the queue
     * @return int
     */
    public static function fetchNumJobs(){
        return Utils::getJobQuery()->count();
    }

    /**
     * Returns ID of the newly inserted and deleted job
     * @throws \Exception
     */
    public static function getJobId(){
        $job = self::isJobOptim() ? new JobOptim() : new Job();
        $job->queue = 'XXX';
        $job->payload = 'XXX';
        $job->attempts = 0;
        $job->reserved_at = time()+ 10000;
        $job->available_at = time()+ 10000;
        $job->created_at = time();
        if (self::isJobOptim()){
            $job->version = 0;
        }

        $job->save();
        $id = $job->id;

        $job->delete();
        return $id;
    }

    /**
     * Generates random float
     * @param float $min
     * @param float $max
     * @return float|int
     */
    public static function randomFloat($min=0.0, $max=1.0){
        return random_int($min, $max - 1) + (mt_rand(0, PHP_INT_MAX - 1) / PHP_INT_MAX );
    }

}
