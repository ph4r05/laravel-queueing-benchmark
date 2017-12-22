<?php

namespace App\Console\Commands;

use App\Benchmark\Random\SystemRand;
use App\Benchmark\Utils;
use App\Jobs\FeederBatchJob;
use App\Jobs\FeederJob;
use App\Jobs\WorkJob;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class ChangeDb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:changeDb
                                {--conn= : Override queue connection for workers, 0=mysql, 1=pgsql, 2=sqlite}
                                {--idx= : 0 to remove, 1 to add queue index}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Changes DB connection in env file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $conn = $this->option('conn');
        if ($conn == '0'){
            DotenvEditor::setKey('DB_CONNECTION', 'mysql');
        } elseif ($conn == '1'){
            DotenvEditor::setKey('DB_CONNECTION', 'pgsql');
        } elseif ($conn == '2'){
            DotenvEditor::setKey('DB_CONNECTION', 'sqlite');
        } elseif (!empty($conn)) {
            DotenvEditor::setKey('DB_CONNECTION', $conn);
        } else {
            $this->output->writeln('Connection not changed');
        }

        DotenvEditor::save();

        $idx = $this->option('idx');
        if (isset($idx) && $idx != ""){
            $idx = intval($idx);
        }

        if ($idx === 1){
            $this->output->writeln('Adding queue index');
            Schema::table('jobs', function(Blueprint $table)
            {
                $table->index('queue');
            });

        } elseif ($idx === 0){
            $this->output->writeln('Removing queue index');
            Schema::table('jobs', function (Blueprint $table)
            {
                $table->dropIndex(['queue']);
            });
        }

    }
}
