<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    const TABLE = 'jobs';

    protected $guarded = array();

    protected $table = self::TABLE;

    public function getDates()
    {
        return array('created_at', 'reserved_at', 'available_at');
    }
}
