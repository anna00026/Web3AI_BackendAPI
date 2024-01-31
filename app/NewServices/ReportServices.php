<?php

namespace App\NewServices;

use App\Enums\AssetsPendingStatusEnum;
use App\Enums\AssetsPendingTypeEnum;
use App\Enums\AssetsTypeEnum;
use App\Models\Assets;
use App\Models\PledgeProfits;
use App\Models\Reports;
use App\Models\Users;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ReportServices
{
    /**
     * @param Carbon $now
     * @return void
     */
    public static function compute(Carbon $now): void
    {
        $where = [
            'day' => $now->toDateString(), #
        ];
        $data = [
            'staking_amount' => Assets::today($now)
                ->where('pending_type', AssetsPendingTypeEnum::Staking->name)
                ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                ->sum('balance'), #
            'withdraw_amount' => Assets::today($now)
                ->where('pending_type', AssetsPendingTypeEnum::Withdraw->name)
                ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                ->sum('balance'), #
            'exchange_airdrop_amount' => Assets::today($now)
                ->where('pending_type', AssetsPendingTypeEnum::ExchangeAirdrop->name)
                ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                ->sum('balance'), #
            'deposit_staking_amount' => Assets::today($now)
                ->where('pending_type', AssetsPendingTypeEnum::DepositStaking->name)
                ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                ->sum('balance'), #
            'staking_reward_loyalty_amount' => Assets::today($now)
                ->where('pending_type', AssetsPendingTypeEnum::StakingRewardLoyalty->name)
                ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                ->sum('balance'), #
            'income_amount' => PledgeProfits::today($now)
                ->sum('income'), #
            'actual_income_amount' => PledgeProfits::today($now)
                ->sum('actual_income'), #
            'withdrawable_amount' => Assets::today($now)
                ->where('type', AssetsTypeEnum::WithdrawAble->name)
                ->sum('balance'), #
            'user_register_count' => Users::today($now)
                ->count(), #
            'user_login_count' => Users::today($now, 'last_login_at')
                ->count(), #
            'staking_count' => Assets::today($now)
                ->where('pending_type', AssetsPendingTypeEnum::Staking->name)
                ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                ->count(), #
            'withdraw_count' => Assets::today($now)
                ->where('pending_type', AssetsPendingTypeEnum::Withdraw->name)
                ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                ->count(), #
            'exchange_airdrop_count' => Assets::today($now)
                ->where('pending_type', AssetsPendingTypeEnum::ExchangeAirdrop->name)
                ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                ->count(),
            'deposit_staking_count' => Assets::today($now)
                ->where('pending_type', AssetsPendingTypeEnum::DepositStaking->name)
                ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                ->count(),
            'staking_reward_loyalty_count' => Assets::today($now)
                ->where('pending_type', AssetsPendingTypeEnum::StakingRewardLoyalty->name)
                ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                ->count(), #
            'trail_count' => Users::today($now, 'trailed_at')
                ->count(), #
            'income_count' => PledgeProfits::today($now)
                ->where('income', '>', 0)
                ->count(), #
            'actual_income_count' => PledgeProfits::today($now)
                ->where('actual_income', '>', 0)
                ->count(), #
            'withdrawable_count' => Assets::today($now)
                ->where('type', AssetsTypeEnum::WithdrawAble->name)
                ->count(), #
        ];
        Reports::updateOrCreate($where, $data);
//        dump($where, $data);
        // cache all data
        $all = Reports::all();
        $fs = ['staking_amount', 'withdraw_amount', 'exchange_airdrop_amount', 'deposit_staking_amount', 'staking_reward_loyalty_amount', 'income_amount', 'actual_income_amount', 'withdrawable_amount', 'user_register_count', 'user_login_count', 'trail_count', 'staking_count', 'withdraw_count', 'exchange_airdrop_count', 'deposit_staking_count', 'staking_reward_loyalty_count', 'income_count', 'actual_income_count', 'withdrawable_count'];
        $data = [];
        foreach ($fs as $f) {
            $data[$f] = $all->sum($f);
        }
//        dump($data);
        Cache::tags(['reports'])->forever('all', $data);
    }

        /**
     * @param Carbon $now
     * @return void
     */
    public static function computeAgent(Carbon $now): void
    {
        $where = [
            'day' => $now->toDateString(), #
        ];
        $users=Users::select('parent_id')->where('parent_id','>',0)->groupBy("parent_id")->get();
        foreach($users as $user){
            $userids=Users::select('id')->where('parent_id',$user->parent_id)->get();
            $where['parent_id']=$user->parent_id;
            $data = [
                'parent_id'=>$user->parent_id,
                'staking_amount' => Assets::today($now)
                    ->whereIn(
                        'users_id',$userids
                    )
                    ->where('pending_type', AssetsPendingTypeEnum::Staking->name)
                    ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                    ->sum('balance'), #
                'withdraw_amount' => Assets::today($now)
                     ->whereIn(
                        'users_id',$userids
                    )
                    ->where('pending_type', AssetsPendingTypeEnum::Withdraw->name)
                    ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                    ->sum('balance'), #
                'exchange_airdrop_amount' => Assets::today($now)
                    ->where('pending_type', AssetsPendingTypeEnum::ExchangeAirdrop->name)
                    ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                    ->sum('balance'), #
                'deposit_staking_amount' => Assets::today($now)
                    ->whereIn(
                        'users_id',$userids
                    )
                    ->where('pending_type', AssetsPendingTypeEnum::DepositStaking->name)
                    ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                    ->sum('balance'), #
                'staking_reward_loyalty_amount' => Assets::today($now)
                    ->whereIn(
                        'users_id',$userids
                    )
                    ->where('pending_type', AssetsPendingTypeEnum::StakingRewardLoyalty->name)
                    ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                    ->sum('balance'), #
                'income_amount' => PledgeProfits::today($now)
                    ->where(
                        'parent_1_id',$user->parent_id,
                    )
                    ->sum('income'), #
                'actual_income_amount' => PledgeProfits::today($now)
                    ->where(
                        'parent_1_id',$user->parent_id,
                    )
                    ->sum('actual_income'), #
                'withdrawable_amount' => Assets::today($now)
                    ->whereIn(
                        'users_id',$userids
                    )
                    ->where('type', AssetsTypeEnum::WithdrawAble->name)
                    ->sum('balance'), #
                'user_register_count' => Users::today($now)
                    ->where(
                        'parent_id',$user->parent_id,
                    )
                    ->count(), #
                'user_login_count' => Users::today($now, 'last_login_at')
                    ->where(
                        'parent_id',$user->parent_id,
                    )
                    ->count(), #
                'staking_count' => Assets::today($now)
                    ->whereIn(
                        'users_id',$userids
                    )
                    ->where('pending_type', AssetsPendingTypeEnum::Staking->name)
                    ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                    ->count(), #
                'withdraw_count' => Assets::today($now)
                    ->whereIn(
                        'users_id',$userids
                    )
                    ->where('pending_type', AssetsPendingTypeEnum::Withdraw->name)
                    ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                    ->count(), #
                'exchange_airdrop_count' => Assets::today($now)
                    ->whereIn(
                        'users_id',$userids
                    )
                    ->where('pending_type', AssetsPendingTypeEnum::ExchangeAirdrop->name)
                    ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                    ->count(),
                'deposit_staking_count' => Assets::today($now)
                    ->whereIn(
                        'users_id',$userids
                    )
                    ->where('pending_type', AssetsPendingTypeEnum::DepositStaking->name)
                    ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                    ->count(),
                'staking_reward_loyalty_count' => Assets::today($now)
                    ->whereIn(
                        'users_id',$userids
                    )
                    ->where('pending_type', AssetsPendingTypeEnum::StakingRewardLoyalty->name)
                    ->where('pending_status', AssetsPendingStatusEnum::SUCCESS->name)
                    ->count(), #
                'trail_count' => Users::today($now, 'trailed_at')->where(
                    'parent_id',$user->parent_id,
                ) ->count(), #
                'income_count' => PledgeProfits::today($now)
                    ->where(
                        'parent_1_id',$user->parent_id,
                    )
                    ->where('income', '>', 0)
                    ->count(), #
                'actual_income_count' => PledgeProfits::today($now)
                    ->where(
                        'parent_1_id',$user->parent_id,
                    )
                    ->where('actual_income', '>', 0)
                    ->count(), #
                'withdrawable_count' => Assets::today($now)
                    ->whereIn(
                        'users_id',$userids
                    )
                    ->where('type', AssetsTypeEnum::WithdrawAble->name)
                    ->count(), #
            ];
            Reports::updateOrCreate($where, $data);
        // cache all data
            $all = Reports::all()->where('parent_id',$user->parent_id);
            $fs = ['staking_amount', 'withdraw_amount', 'exchange_airdrop_amount', 'deposit_staking_amount', 'staking_reward_loyalty_amount', 'income_amount', 'actual_income_amount', 'withdrawable_amount', 'user_register_count', 'user_login_count', 'trail_count', 'staking_count', 'withdraw_count', 'exchange_airdrop_count', 'deposit_staking_count', 'staking_reward_loyalty_count', 'income_count', 'actual_income_count', 'withdrawable_count'];
            $data = [];
            foreach ($fs as $f) {
                $data[$f] = $all->sum($f);
            }
            Cache::tags(['reports_'.$user->parent_id])->forever('all', $data);
        }
        

    }


}
