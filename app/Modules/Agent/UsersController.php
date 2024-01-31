<?php


namespace App\Modules\Agent;


use App\Enums\SysMessageTypeEnum;
use App\Models\SysMessages;
use App\Models\Users;
use App\Modules\AgentBaseController;
use App\NewServices\UsersServices;
use Illuminate\Http\Request;
use JetBrains\PhpStorm\ArrayShape;
use LaravelCommon\App\Exceptions\Err;
use Illuminate\Support\Facades\DB;

/**
 * @intro Users
 * Class UsersController
 * @package App\Modules\Admin
 */
class UsersController extends AgentBaseController
{
    /**
     * @intro 用户列表
     * @param Request $request
     * @return mixed
     * @throws Err
     */
    public function list(Request $request): mixed
    {
        $params = $request->validate([
            'nickname' => 'nullable|string',
            'address' => 'nullable|string',
            'parent_address' => 'nullable|string',
            'trailed' => 'nullable|boolean',
            'email_verified' => 'nullable|boolean',
            'profile_status' => 'nullable|string', # 1:Default / 2:Waiting / 3:OK
            'identity_status' => 'nullable|string', # 1:Default / 2:Waiting / 3:OK
            'status' => 'nullable|string',
            'user_vips_id' => 'nullable|integer',
            // 'is_account_proxy'=>'nullable|integer', // 0:all / 1:代理 / 2:无代理
            // 'username'=>'nullable|string',
        ]);
        $params['is_cool_user'] = false;
        // if(array_key_exists('username',$params) && $params['username']!=''){
        //     $params['is_account_proxy']=1;
        // }
        $user = $this->getUser();
        return $this->getQuery($params, $user)
            ->paginate($this->perPage())->map(function ($u) {
                $u->level = $u->getLevel();
                return $u;
            });
    }

    /**
     * @intro 查看用户发送的留言信息
     * @param Request $request
     * @return mixed
     * @throws Err
     */
    public function userMessage(Request $request): mixed
    {
        $params = $request->validate([
            'id' => 'required|integer', # id
        ]);
        $user = UsersServices::GetById($params['id']);
        return SysMessages::where('users_id', $user->id)
            ->myChildren($user)
            ->where('type', SysMessageTypeEnum::FriendMessage->name)
            ->paginate($this->perPage());
    }

    /**
     * @param array $params
     * @param Users $user
     * @return mixed
     */
    private function getQuery(array $params, Users $user): mixed
    {
        return Users::withParents()
            ->select('*', DB::raw("IF(username is NULL,'',username) as username,IF(username is NULL,2,1) as is_account_proxy"))
            ->where(function ($q1) use ($user) {
                $q1->where('parent_1_id', $user->id)
                    ->orWhere('parent_2_id', $user->id)
                    ->orWhere('parent_3_id', $user->id);
            })
            ->with('parent_1:id,address')
            ->with('vip:id,name')
            ->order()
            ->when(!isset($params['download_type']) || $params['download_type'] == 2, function ($q) use ($params) {
                return $q
                    ->ifWhereLike($params, 'nickname')
                    ->ifWhereLike($params, 'full_name')
                    ->ifWhereLike($params, 'address')
                    ->ifWhereHas($params, 'parent_address', 'parent_1', function ($q) use ($params) {
                        $q->where('address', 'like', "%{$params['parent_address']}%");
                    })
                    ->when(isset($params['trailed']), function ($q) use ($params) {
                        return $params['trailed'] ? $q->where('trailed_at', '!=', null) : $q->where('trailed_at', null);
                    })
                    ->when(isset($params['email_verified']), function ($q) use ($params) {
                        return $params['email_verified'] ? $q->where('email_verified_at', '!=', null) : $q->where('email_verified_at', null);
                    })
                    ->ifWhere($params, 'profile_status')
                    ->ifWhere($params, 'identity_status')
                    ->ifWhere($params, 'status')
                    ->ifWhere($params, 'is_cool_user')
                    ->ifWhere($params, 'user_vips_id', 'vips_id');
            });
    }

    /**
     * @intro show for detail
     * @param Request $request
     * @return array
     * @throws Err
     */
    #[ArrayShape(['model' => "", 'statistics' => "array[]"])]
    public function show(Request $request): array
    {
        $params = $request->validate([
            'id' => 'required|integer', # id
        ]);
        $user = $this->getUser();
        $model = Users::withParents()
            ->where(function ($q1) use ($user) {
                $q1->where('parent_1_id', $user->id)
                    ->orWhere('parent_2_id', $user->id)
                    ->orWhere('parent_3_id', $user->id);
            })
            ->with('vip:id,name')
            ->idp($params);
        return [
            'model' => $model,
            'statistics' => [
                ['title' => __('Total Balance'), 'value' => $model->total_balance, 'type' => 'money'],
                ['title' => __('Total Staking'), 'value' => $model->total_staking_amount, 'type' => 'money'],
                ['title' => __('Total Loyalty'), 'value' => $model->total_loyalty_value, 'type' => 'money'],
            ]
        ];
    }

}
