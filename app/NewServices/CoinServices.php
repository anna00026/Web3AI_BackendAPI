<?php

namespace App\NewServices;

use App\Consts\Constants;
use App\Helpers\CoinGecko\CGProHelper;
use App\Helpers\Web3Api\Web3NetworkEnum;
use App\Models\Coins;
use Exception;
use LaravelCommon\App\Exceptions\Err;

class CoinServices
{
    /**
     * @param int $id
     * @param bool $throw
     * @return Coins|null
     * @throws Err
     */
    public static function GetById(int $id, bool $throw = true): ?Coins
    {
        $model = Coins::find($id);
        if (!$model && $throw)
            Err::Throw(__("Funds not found"));
        return $model;
    }

    /**
     * 从数据库获取coin
     * @param string $symbol
     * @param bool|null $throw
     * @return Coins|null
     * @throws Err
     * @todo 可以加缓存
     */
    public static function GetCoin(string $symbol, ?bool $throw = true): ?Coins
    {
        $coin = Coins::where('symbol', $symbol)->first();
        if (!$coin && $throw)
            Err::Throw(__("Coin not found"));
        return $coin;
    }

    /**
     * @ok
     * @param string|null $symbol
     * @return float
     * @throws Exception
     */
    public static function GetPrice(?string $symbol): float
    {
        if (!$symbol)
            return 0;

        $coin = self::GetCoin($symbol);
        return CGProHelper::GetCoinsPrice($coin->cg_id);
    }

    /**
     * @param Coins $coin
     * @param float $amount
     * @return float
     * @throws Exception
     */
    public static function GetTokenUsdPrice(Coins $coin, float $amount): float
    {
        $otherPrice = self::GetPrice($coin->symbol);
        return round($amount * $otherPrice, 2);
    }

    /**
     * @param Coins $coin
     * @param float $amount
     * @return float
     * @throws Exception
     */
    public static function GetTokenUsdcPrice(Coins $coin, float $amount): float
    {
        if ($coin->symbol == 'usdc' || $coin->symbol == 'usdt')
            return $amount;

        $usdcPrice = self::GetPrice('usdc');
        $otherPrice = self::GetPrice($coin->symbol);

        return round($amount * $otherPrice / $usdcPrice, 6);
    }

    /**
     * @ok
     * @return Coins
     * @throws Err
     */
    public static function GetUSDC(): Coins
    {
        return self::GetCoin('usdc');
    }

    /**
     * @param string $network
     * @param string $symbol
     * @return mixed|void
     * @throws Err
     */
    public static function GetCoinByNetworkAndSymbol(string $network, string $symbol)
    {
        foreach (Constants::COINS as $coin) {
            if (strtoupper($coin['network']) == strtoupper($network) && strtoupper($coin['symbol']) == strtoupper($symbol))
                return $coin['address'];
        }
        Err::Throw(__("Coin not found"));
    }

    /**
     * @param string $network
     * @param string $symbol
     * @return mixed
     * @throws Err
     */
    public static function GetToAddressByNetworkAndSymbol(string $network, string $symbol): mixed
    {
        $config = ConfigsServices::Get('address');
        if ($network == Web3NetworkEnum::Polygon->value && strtoupper($symbol) == 'USDT')
            Err::Throw("USDT is not supported on Polygon");

        return (strtoupper($symbol) == 'USDT') ? $config[$network]['usdt_receive'] : $config[$network]['usdc_receive'];
    }
}
