<?php

namespace App\NewServices;

use App\Enums\CacheTagsEnum;
use App\Models\Configs;
use Illuminate\Support\Facades\Cache;

class ConfigsServices
{
    /**
     * 获取配置参数
     * @param string $key
     * @return array|null
     */
    public static function Get(string $key): ?array
    {
        $config = self::GetConfig();
//        if (!isset($config[$key]))
//            Err::Throw(__("Config is not ready"));
        return $config[$key] ?? null;
    }

    /**
     * 保存配置参数
     * @param array $params
     * @return void
     */
    public static function Save(array $params): void
    {
        $config = Configs::first();
        if ($config) {
            $config->update([
                $params['key'] => $params['value']
            ]);
        } else {
            Configs::create([
                $params['key'] => $params['value']
            ]);
        }
//        self::CleanCache();
        self::CacheAll();
    }

    /**
     * 获取所有配置
     * @return mixed
     */
    public static function GetConfig(): mixed
    {
        $config = Cache::tags([CacheTagsEnum::Configs->name])->get('config');
        if (!$config)
            $config = self::CacheAll();
        return $config;
    }

    /**
     * @return array
     */
    public static function CacheAll(): array
    {
        $config = Configs::first()->toArray();
        $arr = [];
        foreach ($config as $key => $value) {
            if (in_array($key, ['id', 'created_at', 'updated_at']))
                continue;
            $arr[$key] = json_decode($value, true);
        }
        Cache::tags([CacheTagsEnum::Configs->name])->put('config', $arr);
        return $arr;
    }

    /**
     * @return void
     */
    public static function CleanCache(): void
    {
        Cache::tags([CacheTagsEnum::Configs->name])->clear();
    }
}
