<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ApidocGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apidoc:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Apidoc';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $projectRootPath = base_path();

        shell_exec("chmod +x $projectRootPath/doctum.phar");
        shell_exec("$projectRootPath/doctum.phar update doctum.php");

        return Command::SUCCESS;
    }
}
