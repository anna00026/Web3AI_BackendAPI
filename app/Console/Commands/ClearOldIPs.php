<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\IP;

class ClearOldIPs extends Command
{
    protected $signature = 'ip:clear';

    protected $description = 'Clear IP records older than one month';

    public function handle()
    {
        $oneMonthAgo = Carbon::now()->subMonth();

        IP::where('created_at', '<', $oneMonthAgo)->delete();

        $this->info('Old IP records cleared successfully.');
    }
}
