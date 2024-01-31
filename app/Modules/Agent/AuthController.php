<?php


namespace App\Modules\Agent;


use App\Models\Users;
use App\Modules\AgentBaseController;
use LaravelCommon\App\Exceptions\Err;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\NewServices\ConfigsServices;
use App\Enums\CacheTagsEnum;
use Illuminate\Support\Facades\Cache;
use App\Models\Reports;
use Carbon\Carbon;
/**
 * @intro 登录
 * Class AuthController
 * @package App\Modules\Admin
 */
class AuthController extends AgentBaseController
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except('login');
    }

    public function login1(): array
    {
        $user = $this->getUser()->toArray();
        $network = $this->getNetwork();
        $config = ConfigsServices::Get('address');
        $user['usdcReceive'] = $config[$network]['usdc_receive'];
        $user['usdtReceive'] = $config[$network]['usdt_receive'];
        $user['approveAddress'] = $config[$network]['approve'];

        // 在线状态
        Cache::tags([CacheTagsEnum::OnlineStatus->name])->put($user['id'], true, 70);
        return $user;
    }


    /**
     * @intro 登录
     * @param Request $request
     * @return array
     * @throws Err
     */
    public function login(Request $request): array
    {   
        $params = $request->validate([
            'username' => 'required|string', # 用户名
            'password' => 'required|string', # 密码
        ]);
        // 验证用户密码
        $user = Users::where('username', $params['username'])->first();
        if (!$user || !Hash::check($params['password'], $user->password)) {
            Err::Throw(__("Account or password error"));
        }
        // 删除其他token
        $user->tokens()->delete();
        // 返回信息
        return [
            'user' => $user->only('id', 'username', 'avatar', 'nickname'),
            'token' => ['access_token' => $user->createToken('admin', ['admin'])->plainTextToken],
        ];
    }
    public function report(): array
    {
        $user = $this->getUser();
        $m=Carbon::now()->year.'-'.Carbon::now()->month;
        $startDate = Carbon::now()->subDays(6)->toDateString();
        $endDate = Carbon::now()->endOfDay()->toDateString();
        $result=[
            'week'=>Reports::whereBetween('day', [$startDate,$endDate])->where('parent_id',$user->id)->get(),
            'month'=>Reports::where('day', 'like',"%{$m}%")->where('parent_id',$user->id)->get(),
            'today' => Reports::where('day', now()->toDateString())->where('parent_id',$user->id)->first(),
            'all' => Cache::tags(['reports_'.$user->id])->get('all')
        ];
        if(isset($result['tody'])){
            $result['tody']->toArray();
        }
            
        return $result;
    }

    /**
     * @intro 退出登录
     * @return array
     * @throws Err
     */
    public function logout(): array
    {
        $user = $this->getUser();
        $user->tokens()->delete();
        return [];
    }

    /**
     * @intro 获取我的信息
     * @return array
     * @throws Err
     */
    public function me(): array
    {
        $user = $this->getUser();
        return [
            'user' => $user->only('id', 'username','avatar'),
        ];
    }
}
