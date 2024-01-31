<?php

namespace App\Console\Commands;

use App\Models\Users;
use App\NewServices\ConfigsServices;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class TestCommand extends Command
{
    protected $signature = 'TestCommand';
    protected $description = 'Command description';

    /**
     * @return int
     */
    public function handle(): int
    {
        Users::fixTree();
//        ConfigsServices::CacheAll();
        return CommandAlias::SUCCESS;
    }
}
