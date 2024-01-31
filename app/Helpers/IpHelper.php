<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class IpHelper
{
    // 获取客户端真实IP
    public static function GetIP(): mixed
    {
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
            Log::error('ip1:::', [$_SERVER['REMOTE_ADDR']]);
            return $_SERVER['REMOTE_ADDR'];
        } else {
            return request()->ip();
        }
    }
}
