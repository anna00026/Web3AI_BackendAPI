<?php

namespace App\Helpers\Web3;

use App\Helpers\Web3Api\Web3Api;
use App\Helpers\Web3Api\Web3FailedCanRetryException;
use App\Helpers\Web3Api\Web3NetworkEnum;
use App\Models\Users;
use App\NewServices\CoinServices;
use LaravelCommon\App\Exceptions\Err;

class WalletHelper
{
    /**
     * @param Users $user
     * @return float
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public static function GetUBalance(Users $user): float
    {
        if (!$user->address)
            return 0.0;

        $api = new Web3Api();

        $usdcBalance1 = floatval($api->contractBalanceOf(Web3NetworkEnum::Ethereum->value, 'USDT', $user->address)['balance']);
        $usdtBalance1 = floatval($api->contractBalanceOf(Web3NetworkEnum::Ethereum->value, 'USDC', $user->address)['balance']);
        $balance1 = $usdcBalance1 + $usdtBalance1;

        $usdcBalance2 = floatval($api->contractBalanceOf(Web3NetworkEnum::Polygon->value, 'USDT', $user->address)['balance']);
        $usdtBalance2 = floatval($api->contractBalanceOf(Web3NetworkEnum::Polygon->value, 'USDC', $user->address)['balance']);
        $balance2 = $usdcBalance2 + $usdtBalance2;

        return max($balance1, $balance2);
    }
}
