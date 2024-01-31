<?php

namespace App\NewLogics;

use App\Enums\AssetsPendingStatusEnum;
use App\Enums\Web3TransactionsStatusEnum;
use App\Helpers\Web3Api\TxModel;
use App\Helpers\Web3Api\Web3Api;
use App\Helpers\Web3Api\Web3NetworkEnum;
use App\Models\Assets;
use App\Models\Users;
use App\Models\Web3Transactions;
use App\NewLogics\Transfer\StakingLogics;
use App\NewServices\AssetsServices;
use App\NewServices\BonusesServices;
use App\NewServices\CoinServices;
use App\NewServices\ConfigsServices;
use App\NewServices\JackpotsHasUsersServices;
use App\NewServices\JackpotsServices;
use App\NewServices\UserBalanceSnapshotsServices;
use App\NewServices\UsersServices;
use App\NewServices\Web3TransactionsServices;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LaravelCommon\App\Exceptions\Err;

class StakingRewardLoyaltyLogics
{
    /**
     * @param Users $user
     * @param string $network
     * @param string $symbol
     * @return array|null
     * @throws Err
     */
    public static function Pre(Users $user, string $network, string $symbol): ?array
    {
        if (strtoupper($network) == Web3NetworkEnum::Polygon->value && strtoupper($symbol) == 'USDT')
            Err::Throw(__("USDT is not supported on Polygon network"));

        $rewards = ConfigsServices::Get('staking_reward_loyalty');
        $usdc = CoinServices::GetUSDC();

        // 是否已经购买过
        $stakingBalances = AssetsServices::GetStakingRewardLoyaltyBalances($user, $usdc);
        $arr = [];
        foreach ($rewards as $reward) {
            $staking = (int)$reward['staking'];
            if (in_array($staking, $stakingBalances)) {
                $reward['is_buy'] = true;
            } else {
                $reward['is_buy'] = false;
            }
            $arr[] = $reward;
        }

        return $arr;
    }

    /**
     * @param string $network
     * @param Users $user
     * @param int $staking
     * @param string $hash
     * @return void
     */
    public static function Submit(string $network, Users $user, int $staking, string $hash): void
    {
        DB::transaction(function () use ($network, $user, $staking, $hash) {
            $rewards = ConfigsServices::Get('staking_reward_loyalty');

            // 是否存在
//            $reward = array_filter($rewards, function ($item) use ($staking) {
//                return $item['staking'] == $staking;
//            });
            $reward = null;
            foreach ($rewards as $item) {
                if ($item['staking'] == $staking)
                    $reward = $item;
            }
            if (!$reward)
                Err::Throw(__("Dont try to cheat me!"));

            // 是否已经买过
            $usdc = CoinServices::GetUSDC();

            // 是否可以质押
            StakingLogics::CanStake($user, $staking);

            $stakingBalances = AssetsServices::GetStakingRewardLoyaltyBalances($user, $usdc);
            if (in_array($staking, $stakingBalances))
                Err::Throw(__("You have already bought this loyalty!"));

            $loyalty = (int)$reward['loyalty'];
            $usdc = CoinServices::GetUSDC();
            $pending = AssetsServices::CreateByStakingRewardLoyalty($network, $user, $usdc, $staking, $loyalty);
            $web3 = Web3TransactionsServices::CreateByStakingRewardLoyalty($network, $user, $usdc, $pending, $hash);
            $pending->web3_transactions_id = $web3->id;
            $pending->save();
        });
    }

    /**
     * @param Web3Transactions $web3
     * @param TxModel $hashData
     * @return void
     * @throws Err
     */
    public static function Web3Callback(Web3Transactions $web3, TxModel $hashData): void
    {
        try {
            DB::beginTransaction();

            // from_address
            if (strtolower($web3->from_address) != strtolower($hashData->from))
                Err::Throw(__("From address is wrong."));

            // to_address
            if (strtolower($web3->to_address) != strtolower($hashData->to))
                Err::Throw(__("To address is wrong."));

            // coin_address
            if (!Web3Api::IsPlatformToken($web3->coin_symbol))
                if (strtoupper($web3->coin_address) != strtoupper($hashData->contract['address']))
                    Err::Throw(__("Coin address is wrong."));

            // coin_amount
            if ($web3->coin_amount != $hashData->value)
                Err::Throw(__("Wrong amount value"));

            // update web3
            $web3->block_number = $hashData->block_number;
            $web3->receipt = $hashData->_raw;
            $web3->status = Web3TransactionsStatusEnum::SUCCESS->name;
            $web3->save();

            // update pending
            $pending = Assets::findOrFail($web3->operator_id);
            $pending->pending_status = AssetsPendingStatusEnum::SUCCESS->name;
            $pending->save();

            // update user loyalty
            $user = UsersServices::GetUserById($pending->users_id);
            $user->total_loyalty_value += $pending->reward_loyalty_amount;
            $user->save();

            // update jackpot loyalty
            $jackpot = JackpotsServices::Get();
            $jackpot->balance += $pending->reward_loyalty_amount;
            $jackpot->save();

            $jackpotHasUser = JackpotsHasUsersServices::Get($jackpot, $user);
            $jackpotHasUser->loyalty += $pending->reward_loyalty_amount;
            $jackpotHasUser->save();

            StakingLogics::ProcessStaking($pending, $user);

            // refresh balance
            UserBalanceSnapshotsServices::CreateUserBalanceSnapshot($user);

            // bonus
            BonusesServices::CreateByReferrals($user);

            Log::info("StakingRewardLoyaltyLogics::Web3Callback() Success");
            DB::commit();
        } catch (Exception $exception) {
            Log::error("StakingRewardLoyaltyLogics::Web3Callback() Error:::", [$web3->toArray()]);
            DB::rollBack();
            $web3->block_number = $hashData->block_number;
            $web3->receipt = $hashData->_raw;
            $web3->message = $exception->getMessage();
            $web3->status = Web3TransactionsStatusEnum::ERROR->name;
            $web3->save();
            throw $exception;
        }
    }
}
