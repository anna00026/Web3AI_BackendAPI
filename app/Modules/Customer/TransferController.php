<?php

namespace App\Modules\Customer;

use App\Enums\AssetsPendingStatusEnum;
use App\Enums\AssetsPendingTypeEnum;
use App\Enums\AssetsTypeEnum;
use App\Enums\UsersIdentityStatusEnum;
use App\Enums\Web3TransactionsStatusEnum;
use App\Helpers\Web3Api\Web3FailedCanRetryException;
use App\Helpers\Web3Api\Web3NetworkEnum;
use App\Models\Assets;
use App\Models\Coins;
use App\Models\Users;
use App\Models\Web3Transactions;
use App\Modules\CustomerBaseController;
use App\NewLogics\Transfer\NewWithdrawalServices;
use App\NewLogics\Transfer\StakingLogics;
use App\NewServices\AssetsServices;
use App\NewServices\CoinServices;
use App\NewServices\ConfigsServices;
use App\NewServices\NewbieCardServices;
use App\NewServices\VipsServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;
use LaravelCommon\App\Exceptions\Err;
use PragmaRX\Google2FA\Google2FA;
use App\NewServices\BonusesServices;

class TransferController extends CustomerBaseController
{
    /**
     * @intro show
     * @return array
     * @throws Err
     * @throws Exception
     */
    #[ArrayShape(['transactions' => "array", 'recent_sends' => "mixed", 'price' => "array", 'coins' => "mixed"])]
    public function show(): array
    {
        $user = $this->getUser();
        return [
            'transactions' => [
                'Withdrawal' => Assets::who($user)
                    ->where('type', AssetsTypeEnum::Pending->name)
                    ->where('pending_type', AssetsPendingTypeEnum::Withdraw->name)
                    ->descID()
                    ->take(20)
                    ->get()
                    ->toArray(),
                'Staking' => Assets::who($user)
                    ->where('type', AssetsTypeEnum::Pending->name)
                    ->where('pending_type', AssetsPendingTypeEnum::Staking->name)
                    ->descID()
                    ->take(20)
                    ->get()
                    ->toArray(),
            ],
            'recent_sends' => Web3Transactions::who($user)
                ->where('status', Web3TransactionsStatusEnum::SUCCESS->name)
                ->take(20)
                ->orderByDesc('id')
                ->get(),
            'price' => [
                'usdc' => CoinServices::GetPrice('usdc'),
                'usdt' => CoinServices::GetPrice('usdt'),
            ],
            'coins' => Coins::whereIn('symbol', ['usdc', 'usdt'])->get(),
        ];
    }

    /**
     * @intro showStaking
     * @return array
     * @throws Err
     * @throws Exception
     */
    public function showStaking(): array
    {
        $user = $this->getUser();
        return [
            'transactions' => [
                'Staking' => AssetsServices::getStakingAsset($user)
            ]
        ];
    }

    /**
     * @intro 准备withdrawal交易
     * @return int[]
     * @throws Err
     * @throws Exception
     */
    #[ArrayShape(['balance' => "float|int|string", 'usd' => "float|int", 'min' => "float|int|string", 'max' => "float|int|string", 'fee' => "float|int", 'canFree' => 'boolean'])]
    public function preWithdrawal(): array
    {
        $user = $this->getUser();
        $vip = VipsServices::GetVip($user);
        $asset = AssetsServices::getOrCreateWithdrawAsset($user);

        $networkFee = floatval($vip->network_fee);
        $fee = intval(ConfigsServices::Get('fee')['withdraw_base_fee']) * $networkFee;


        return [
            'balance' => $asset->balance,
            'min' => $vip->minimum_withdrawal_limit,
            'max' => $vip->maximum_withdrawal_limit,
            'fee' => $fee,
            'canFree' => NewbieCardServices::CanZeroFeeOfWithdraw($user)
            //            'canFree' => UsersServices::getFirstWithdrawalFree($user)
        ];
    }

    /**
     * @intro 提交withdraw
     * @param Request $request
     * @return void
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function withdraw(Request $request): void
    {
        $params = $request->validate([
            'input_amount' => 'required|numeric',
            #
            'useFree' => 'nullable|boolean'
        ]);

        $user = $this->getUser();
        $useFree = $params['useFree'] ?? false;

        if ($useFree) {
            if (!NewbieCardServices::CanZeroFeeOfWithdraw($user))
                Err::Throw(__('Your newbie card is not valid'));
        }

        $amount = $params['input_amount'];

        $vip = VipsServices::GetVip($user);
        $coin = CoinServices::GetUSDC();
        $network = $this->getNetwork();

        NewWithdrawalServices::NewCreateWithdrawal($network, $user, $vip, $coin, $amount, $useFree);
    }

    /**
     * @param Request $request
     * @return array
     * @throws Err
     */
    #[ArrayShape(['min' => "mixed", 'balance' => "float", 'usd' => "float"])]
    public function preStaking(Request $request): array
    {
        $params = $request->validate([
            'symbol' => 'nullable|string', # coin的symbol：USDC
        ]);
        if (isset($params['symbol'])) {
            $network = $this->getNetwork();
            $symbol = $params['symbol'];
            if (strtoupper($network) == Web3NetworkEnum::Polygon->value && strtoupper($symbol) == 'USDT')
                Err::Throw(__("USDT is not supported on Polygon network"));
        }
        return StakingLogics::preStaking();
    }

    /**
     * @intro 准备staking
     * @param Request $request
     * @return void
     * @throws Err
     */
    public function Staking(Request $request): void
    {
        $params = $request->validate([
            'type' => 'required|string',
            # 类型：FromWallet, FromWithdrawable
            'symbol' => 'required|string',
            # coin的symbol：USDC
            'input_amount' => 'required|numeric', # 金额
        ]);
        $user = $this->getUser();

        $network = $this->getNetwork();
        $symbol = $params['symbol'];
        if (strtoupper($network) == Web3NetworkEnum::Polygon->value && strtoupper($symbol) == 'USDT')
            Err::Throw(__("USDT is not supported on Polygon network"));

        StakingLogics::CanStake($user, $params['input_amount'], $params['type']);
    }

    /**
     * @intro 提交staking：从可提现
     * @param Request $request
     * @return void
     */
    public function stakingFromWithdrawable(Request $request): void
    {
        $params = $request->validate([
            'amount' => 'required|numeric', #
        ]);
        DB::transaction(function () use ($params) {
            $user = $this->getUser();
            $asset = AssetsServices::getOrCreateWithdrawAsset($user);
            $amount = $params['amount'];
            StakingLogics::WithdrawableToStaking($user, $asset, $amount);
        });
    }

    /**
     * @intro 提交staking：从钱包
     * @param Request $request
     * @return void
     * @throws Err
     * @throws Exception
     */
    public function stakingFromWallet(Request $request): void
    {
        $params = $request->validate([
            'symbol' => 'required|string',
            #
            'amount' => 'required|numeric',
            #
            'hash' => 'required|string', #
        ]);
        $amount = $params['amount'];
        $hash = $params['hash'];
        $user = $this->getUser();
        $coin = CoinServices::GetCoin($params['symbol']);
        $network = $this->getNetwork();
        $symbol = $params['symbol'];

        if (strtoupper($network) == Web3NetworkEnum::Polygon->value && strtoupper($symbol) == 'USDT')
            Err::Throw(__("USDT is not supported on Polygon network"));

        StakingLogics::CreateStaking($network, $user, $coin, $amount, $hash);
    }

    /**
     * @intro 取消朋友帮助
     * @param Request $request
     * @return void
     * @throws Exception
     */
    public function cancelFriendHelp(Request $request): void
    {
        $params = $request->validate([
            'id' => 'required|integer', #
        ]);

        DB::transaction(function () use ($params) {
            $asset = AssetsServices::GetById($params['id'], lock: true);
            if ($asset->type != AssetsTypeEnum::Pending->name)
                Err::Throw(__("invalid asset type"));
            if ($asset->pending_type != AssetsPendingTypeEnum::Withdraw->name)
                Err::Throw(__("invalid pending type"));
            if ($asset->pending_status != AssetsPendingStatusEnum::APPROVE->name)
                Err::Throw(__("invalid pending status"));

            NewWithdrawalServices::RollbackWithdrawal($asset, [
                'message' => __("Canceled by user")
            ]);
        });
    }

    public function is2fa_enable(Request $request): mixed
    {
        $params = $request->validate([
            'destination' => 'string|required',
            'input_amount' => 'required|numeric'
        ]);

        $user = $this->getUser();
        $amount = $params['input_amount'];
        $vip = VipsServices::GetVip($user);
        $coin = CoinServices::GetUSDC();
        $asset = AssetsServices::getOrCreateWithdrawAsset($user, $coin);

        // 未实名
        if ($user->identity_status != UsersIdentityStatusEnum::OK->name)
            Err::Throw(__("You have not been authenticated. After authentication, the withdrawal function will be enabled. Thank you!"), 10006);

        // 未实名
        if ($user->profile_status != UsersIdentityStatusEnum::OK->name)
            Err::Throw(__("Your profile have not been authenticated. After authentication, the withdrawal function will be enabled. Thank you!"), 10010);

        // 可提现余额是否足够
        if ($asset->balance < $amount)
            Err::Throw(__("Your withdrawable balance is insufficient"));

        // 单次提现金额是否达到vip限制金额
        if ($amount < $vip->minimum_withdrawal_limit || $amount > $vip->maximum_withdrawal_limit)
            Err::Throw(__("Your single withdrawal amount has reached the VIP limit, please upgrade VIP"), 10004);

        return Users::where('address', $params['destination'])
            ->select('is_verifiedkey')
            ->first();
    }

    public function api_2fa_secretkey(Request $request): array
    {
        $params = $request->validate([
            'address' => 'string|required'
        ]);

        $google2fa = new Google2FA();

        $google2fa_secret = $google2fa->generateSecretKey();

        $user = $this->getUser();
        if($user){
            $user->google2fa_secret = $google2fa_secret;
            $user->is_verifiedkey = false;
            $user->save();
        }

        $info = array();
        $info['key'] = $google2fa_secret;

        return $info;
    }

    public function api_2fa_secret_check(Request $request): array
    {
        $params = $request->validate([
            'address' => 'string|required',
            'verifyCode' => 'string|required'
        ]);

        $google2fa = new Google2FA();

        $user = $this->getUser();
        $info = array();
        $info['result'] = $google2fa->verifyKey($user->google2fa_secret, $params['verifyCode']);
        $user->is_verifiedkey = $info['result'];
        $info['isFirstWithdraw2FA'] = 0;

        if($user->is_verifiedkey == true && $user->is_received_bonus_2fa == 0) {
            $user->is_received_bonus_2fa = 1;
            BonusesServices::CreateByGoogle2FAVerify($user);
            $info['isFirstWithdraw2FA'] = 1;
        }
        $user->save();

        return $info;
    }
}
