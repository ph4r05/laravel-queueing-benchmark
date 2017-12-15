<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobOptim extends Model
{
    const TABLE = 'jobs_optim';

    protected $guarded = array();

    protected $table = self::TABLE;

    public $timestamps = false;

    public function getDates()
    {
        return array();
    }
}
