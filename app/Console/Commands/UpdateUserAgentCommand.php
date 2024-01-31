<?php

namespace App\Console\Commands;

use App\Models\Users;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class UpdateUserAgentCommand extends Command
{
    protected $signature = 'UpdateUserAgentCommand';
    protected $description = 'Command description';

    /**
     * @return int
     */
    public function handle(): int
    {
        Users::with('ancestors')
            ->orderBy('_lft', 'desc')
            ->whereNotNull('parent_1_id')
            ->each(function (Users $user) {
                $parents = $user->toArray()['ancestors'] ?? [];
                for ($i = count($parents) - 1; $i >= 0; $i--) {
                    if ($parents[$i]['username'] != null && $parents[$i]['username'] != '') {
                        $user->update([
                            'agent_users_id' => $parents[$i]['id']
                        ]);
                        $this->line('Update user ' . $user->id . ' agent to ' . $parents[$i]['username']);
                        return;
                    }
                }
            });
        return CommandAlias::SUCCESS;
    }
}
