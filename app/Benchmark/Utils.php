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

}
