<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Protocol extends Model
{
    const TABLE = 'protocol';

    protected $guarded = array();

    protected $table = self::TABLE;

    public $timestamps = false;

    public function getDates()
    {
        return array();
    }
}
