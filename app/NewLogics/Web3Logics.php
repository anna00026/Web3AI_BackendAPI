<?php

namespace App\NewLogics;


use App\Enums\Web3TransactionsStatusEnum;
use App\Helpers\Web3Api\Web3Api;
use App\Helpers\Web3Api\Web3FailedCanRetryException;
use App\Models\Web3Transactions;
use Exception;
use LaravelCommon\App\Exceptions\Err;

class Web3Logics
{
    /**
     * @param Web3Transactions $web3
     * @return void
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public static function SendCryptoToUser(Web3Transactions $web3): void
    {
        try {
            $api = new Web3Api();
            if (Web3Api::IsPlatformToken($web3->coin_symbol)) {
                $hash = $api->send(
                    $web3->coin_network,
                    $web3->to_address,
                    $web3->coin_amount,
                );
            } else {
                $hash = $api->contractTransfer(
                    $web3->coin_network,
                    $web3->coin_symbol,
                    $web3->to_address,
                    $web3->coin_amount,
                );
            }
            $web3->status = Web3TransactionsStatusEnum::PROCESSING->name;
            $web3->hash = $hash;
            $web3->save();

        } catch (Exception $exception) {
            $web3->status = Web3TransactionsStatusEnum::ERROR->name;
            $web3->message = $exception->getMessage();
            $web3->save();
            throw $exception;
        }
    }
}
