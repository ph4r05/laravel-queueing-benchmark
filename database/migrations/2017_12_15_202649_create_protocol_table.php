<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProtocolTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('protocol', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('tstamp')->nullable();
            $table->integer('pid')->nullable();
            $table->bigInteger('jid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('protocol');
    }
}
