<?php

namespace App\Console\Commands;

use App\Models\Users;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class UpdateNewbieCommand extends Command
{
    protected $signature = 'UpdateNewbieCommand';
    protected $description = 'Command description';

    /**
     * @return int
     */
    public function handle(): int
    {
      $startDate = now()->subDays(14)->startOfDay()->toDateTimeString();
      Users::whereDate('created_at', '>=', $startDate)
        ->update(['membership_card' => 1]);

      return CommandAlias::SUCCESS;
    }
}
