<?php

namespace App\Console\Commands;

use App\Benchmark\Random\SystemRand;
use App\Benchmark\Utils;
use App\Jobs\FeederBatchJob;
use App\Jobs\FeederJob;
use App\Jobs\WorkJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class ChangeDb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:changeDb
                                {--conn= : Override queue connection for workers, 0=mysql, 1=pgsql, 2=sqlite}';

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
        } else {
            DotenvEditor::setKey('DB_CONNECTION', $conn);
        }

        DotenvEditor::save();
    }
}
