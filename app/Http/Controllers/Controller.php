<?php

namespace App\Http\Controllers;

use App\Helpers\Web3Api\Web3NetworkEnum;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use LaravelCommon\App\Exceptions\Err;
use LaravelCommon\App\Traits\ControllerTrait;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    use ControllerTrait;

    /**
     * @return string
     * @throws Err
     */
    public function getNetwork(): string
    {
        $account = json_decode(request()->header('account'), true);
        if (!$account)
            Err::Throw(__("Account is required"));
        return match ($account['chain']) {
            "Ethereum" => Web3NetworkEnum::Ethereum->value,
            "Polygon", "Chain 137" => Web3NetworkEnum::Polygon->value,
            default => Err::Throw(__("Network is not supported")),
        };
    }
}
