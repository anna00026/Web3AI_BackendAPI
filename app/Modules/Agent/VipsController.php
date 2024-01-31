<?php


namespace App\Modules\Agent;


use App\Models\Vips;
use App\Modules\AgentBaseController;

/**
 * @intro
 * Class VipsController
 * @package App\Modules\Admin
 */
class VipsController extends AgentBaseController
{
    /**
     * @intro 选择
     * @return mixed
     */
    public function select(): mixed
    {
        return Vips::selectRaw('id as value,name as label')->orderBy('id', 'asc')->get();
    }
}
