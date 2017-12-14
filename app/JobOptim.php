<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobOptim extends Model
{
    const TABLE = 'jobs_optim';

    protected $guarded = array();

    protected $table = self::TABLE;

    public function getDates()
    {
        return array('created_at', 'reserved_at', 'available_at');
    }
}
