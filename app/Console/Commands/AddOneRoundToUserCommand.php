<?php

namespace App\Console\Commands;

use App\Models\JackpotLogs;
use App\NewLogics\Pledges\ComputePledgesProfitsLogics;
use App\NewServices\JackpotsServices;
use App\NewServices\UsersServices;
use Exception;
use Illuminate\Console\Command;
use LaravelCommon\App\Exceptions\Err;
use Symfony\Component\Console\Command\Command as CommandAlias;

class AddOneRoundToUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AddOneRoundToUserCommand {address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     * @return int
     * @throws Err
     * @throws Exception
     */
    public function handle(): int
    {
        $address = $this->argument('address');
        $user = UsersServices::GetByAddress($address);
        $jackpot = JackpotsServices::Get();
        ComputePledgesProfitsLogics::ComputeUser($user, $jackpot);
        return CommandAlias::SUCCESS;
    }
}
