<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    const TABLE = 'jobs';

    protected $guarded = array();

    protected $table = self::TABLE;

    public $timestamps = false;

    public function getDates()
    {
        return array();
    }
}
