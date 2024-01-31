<?php


namespace App\Modules\Agent;


use App\Models\PledgeProfits;
use App\Modules\AgentBaseController;
use App\NewLogics\Pledges\ComputePledgesProfitsLogics;
use App\NewServices\JackpotsServices;
use App\NewServices\UsersServices;
use Exception;
use Illuminate\Http\Request;
use LaravelCommon\App\Exceptions\Err;

/**
 * @intro
 * Class PledgeProfitsController
 * @package App\Modules\Admin
 */
class PledgeProfitsController extends AgentBaseController
{
    /**
     * @intro 列表
     * @param Request $request
     * @return mixed
     * @throws Err
     */
    public function list(Request $request): mixed
    {
        $params = $request->validate([
            'user_address' => 'nullable|string', # 用户地址
            'is_demo_user' => 'nullable|boolean', # 0 or 1
            'user_vips_id' => 'nullable|integer', # Vip id
            'is_trail' => 'nullable|boolean', # Yes Or No
            'created_at' => 'nullable|array', # 数组["2020-02-02"，"2020-02-03"]
            'nickname'=>'nullable|string',
        ]);
        $user=$this->getUser();
        return PledgeProfits::withUser()
            ->when(array_key_exists('nickname',$params) && $params['nickname']!='',function($q)use($params){
                $q->where("nickname",$params['nickname']);
            })
            ->where("parent_1_id",$user->id)
            ->ifWhereHasUserAddress($params)
            ->ifWhereHasUserIsDemoUser($params)
            ->ifWhereHasUserVip($params)
            ->ifWhere($params, 'is_trail')
            ->ifRange($params, 'created_at')
            ->order()
            ->paginate($this->perPage());
    }

    /**
     * @param Request $request
     * @return void
     * @throws Err
     * @throws Exception
     */
    public function addOneRound(Request $request): void
    {
        $params = $request->validate([
            'address' => 'nullable|string', # 用户地址
        ]);
        $address = $params['address'];
        $user = UsersServices::GetByAddress($address);
        $jackpot = JackpotsServices::Get();
        ComputePledgesProfitsLogics::ComputeUser($user, $jackpot);
    }
}
