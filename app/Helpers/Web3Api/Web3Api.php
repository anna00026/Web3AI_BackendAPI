<?php

namespace App\Helpers\Web3Api;

use App\Helpers\TelegramBot\TelegramBotApi;
use App\NewServices\ConfigsServices;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LaravelCommon\App\Exceptions\Err;

class Web3Api
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'http://new-web3-api:3000';
    }

    /**
     * @param string $network
     * @return mixed
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function info(string $network): mixed
    {
        return $this->doRequest("/info", [
            'network' => $network
        ]);
    }

    /**
     * @param string $_owner
     * @param string $network
     * @return mixed
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function getBalance(string $network, string $_owner): mixed
    {
        return $this->doRequest("/getBalance", [
            'network' => $network,
            '_owner' => $_owner
        ]);
    }

    /**
     * @param string $network
     * @param string $_to
     * @param float $_value
     * @return array|mixed
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function send(string $network, string $_to, float $_value): mixed
    {
        return $this->doRequest("/send", [
            'network' => $network,
            '_to' => $_to,
            '_value' => $_value,
        ]);
    }

    /**
     * @param string $network
     * @param string $hash
     * @param string|null $address
     * @return TxModel
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function getTransaction(string $network, string $hash, ?string $address = null): TxModel
    {
        $json = $this->doRequest("/getTransaction", [
            'network' => $network,
            'hash' => $hash,
            'address' => $address,
        ]);
        return new TxModel($json['tx']);
    }

    /**
     * @param string $network
     * @param string $symbol
     * @return mixed
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function contractInfo(string $network, string $symbol): mixed
    {
        return $this->doRequest("/contract/info", [
            'network' => $network,
            'symbol' => $symbol,
        ]);
    }

    /**
     * @param string $network
     * @param string $symbol
     * @param string $_owner
     * @return mixed
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function contractBalanceOf(string $network, string $symbol, string $_owner): mixed
    {
        return $this->doRequest("/contract/balanceOf", [
            'network' => $network,
            'symbol' => $symbol,
            '_owner' => $_owner,
        ]);
    }

    /**
     * @param string $network
     * @param string $symbol
     * @param string $_to
     * @param float $_value
     * @return mixed
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function contractTransfer(string $network, string $symbol, string $_to, float $_value): mixed
    {
        return $this->doRequest("/contract/transfer", [
            'network' => $network,
            'symbol' => $symbol,
            '_to' => $_to,
            '_value' => $_value,
        ]);
    }

    /**
     * @param string $network
     * @param string $symbol
     * @param string $_to
     * @param float $_value
     * @return mixed
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function contractEstimateGas(string $network, string $symbol, string $_to, float $_value): mixed
    {
        return $this->doRequest("/contract/estimateGas", [
            'network' => $network,
            'symbol' => $symbol,
            '_to' => $_to,
            '_value' => $_value,
        ]);
    }

    /**
     * @param string $network
     * @param string $address
     * @return mixed
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function isAddress(string $network, string $address): mixed
    {
        return $this->doRequest("/isAddress", [
            'network' => $network,
            'address' => $address,
        ]);
    }

    /**
     * @param string $network
     * @param string $address
     * @param int|null $offset
     * @return mixed
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function scanTransactions(string $network, string $address, ?int $offset = 50): mixed
    {
        return $this->doRequest("/scan/transactions", [
            'network' => $network,
            'address' => $address,
            'offset' => $offset,
        ]);
    }

    /**
     * @param string $network
     * @param string $address
     * @param int|null $offset
     * @return mixed
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function scanTokenTransfers(string $network, string $address, ?int $offset = 50): mixed
    {
        return $this->doRequest("/scan/tokenTransfers", [
            'network' => $network,
            'address' => $address,
            'offset' => $offset,
        ]);
    }

    /**
     * @param string|null $ids
     * @param string|null $vs_currencies
     * @return mixed
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function cgSimplePrice(?string $ids = 'ethereum', ?string $vs_currencies = 'usd'): mixed
    {
        return $this->doRequest("/cg/simple/price", [
            'ids' => $ids,
            'vs_currencies' => $vs_currencies
        ]);
    }

    /**
     * @param string|null $vs_currency
     * @param string|null $ids
     * @param string|null $sparkline
     * @param string|null $price_change_percentage
     * @return mixed
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function cgCoinsMarkets(?string $vs_currency = 'usd', ?string $ids = 'ethereum', ?string $sparkline = 'true', ?string $price_change_percentage = '24h'): mixed
    {
        return $this->doRequest("/cg/coins/markets", [
            'vs_currency' => $vs_currency,
            'ids' => $ids,
            'sparkline' => $sparkline,
            'price_change_percentage' => $price_change_percentage,
        ]);
    }

    /**
     * @param string|null $id
     * @return mixed
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function cgCoins(?string $id = 'ethereum'): mixed
    {
        return $this->doRequest("/cg/coins", [
            'id' => $id,
            'localization' => 'false',
            'tickers' => 'false',
            'market_data' => 'true',
            'community_data' => 'false',
            'developer_data' => 'false',
            'sparkline' => 'true',
        ]);
    }

    /**
     * @param string|null $id
     * @param string|null $vs_currency
     * @param string|null $days
     * @return mixed
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    public function cgCoinsMarketChart(?string $id = 'ethereum', ?string $vs_currency = 'usd', ?string $days = '7'): mixed
    {
        return $this->doRequest("/cg/coins/market_chart", [
            'id' => $id,
            'vs_currency' => $vs_currency,
            'days' => $days
        ]);
    }

    /**
     * @param string $uri
     * @param array $array
     * @return mixed
     * @throws Err
     * @throws Web3FailedCanRetryException
     */
    private function doRequest(string $uri, array $array): mixed
    {
        $res = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])
            ->asJson()
            ->post("$this->baseUrl$uri", $array);
        $json = $res->json();
        if ($res->status() !== 200) {
            $message = $json['message'] ?? 'Web3 api error';
            if ($message == 'Transaction Receipt not found')
                throw new Web3FailedCanRetryException($message);
            elseif (Str::contains($message, "insufficient funds") && Str::contains($uri, "contract_estimateGas")) {
                TelegramBotApi::SendText("预估gas费出错，钱包余额不足");
                Err::Throw(__("Trading is temporarily unavailable, please try again later"));
            } else {
//                TelegramBotApi::SendText("Web3Api接口异常\n$message");
                Err::Throw($message);
            }
        }
        return $json;
    }

    /**
     * @param string $symbol
     * @return bool
     */
    public static function IsPlatformToken(string $symbol): bool
    {
        return in_array(strtoupper($symbol), ['ETH', 'MATIC', 'TRX']);
    }
}
