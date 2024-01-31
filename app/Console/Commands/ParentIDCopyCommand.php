<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Models\Users;
use App\Models\CategoryModel;

class ParentIDCopyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ParentIDCopyCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'the value of parent_1_id copy to parent_id';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        while (true){
            $last_id = Redis::get('copy_id');
            if ($last_id <= 0) {
                $last_id=0;
            }
            $users = Users::where('id','>',$last_id)
                ->select('id','parent_1_id')
                ->limit(10)
                ->get()
                ->toArray();
            if (count($users) > 0) {
                foreach ($users as $user){
                    Redis::set('copy_id',$user['id']);
                    if ($user['parent_1_id']>0 ){
                        $result=Users::where('id',$user['id'])->update(['parent_id'=>$user['parent_1_id']]);
                        echo '用户id',$user['id'],$result==1 ? " parent_id更新成功\n" : " parent_id更新失败\n";
                    }
                    
                }
                continue;
            }
            break;
        }
        Users::fixTree();
        return Command::SUCCESS;
    }
}
