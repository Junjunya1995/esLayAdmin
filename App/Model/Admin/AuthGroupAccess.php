<?php
/**
 * Description of AuthGroupAccess.php.
 * Date: 2017-08-09 16:26
 */

namespace App\Model\Admin;

use App\Traits\ModelTrait;
use think\Model;

class AuthGroupAccess extends Model
{

    use ModelTrait;

    /**
     * 把用户添加到用户组
     * @param array $user_ids 用户或者多个用户
     * @param int   $group_id 用户组
     * @return array
     */

    public function addToGroup(array $user_ids = [], int $group_id = 0)
    {
        $repeat = [];
        foreach ($user_ids as $v) {
            //检查用户是否已经所在该用户组
            $object = $this::get(function ($query) use ($v, $group_id) {
                $query->where('group_id',$group_id)->where('uid',$v)->field('uid');
            });
            if ($object) {
                $repeat[] = $object->uid;
                continue;
            }
            $this::create(['group_id' => $group_id, 'uid' => $v]);
        }
        return empty($repeat) ? true : '用户编号' . implode(',', $repeat) . '已经加入该组，不再重复添加';
    }

    /**
     * 查询用户组
     * @param int $uid 用户ID
     * @param int $group_id 用户组ID
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */

    public function removeFromGroup(int $uid, int $group_id)
    {
        return $this::where('uid', $uid)->where('group_id', $group_id)->delete();
    }

    /**
     * 用户添加到用户组
     * @param int $user_id 用户
     * @param array $group_id 用户组或者多个用户组
     * @author staitc7 <static7@qq.com>
     * @return array
     */

    public function userToGroup(int $user_id, array $group_id = [])
    {
        //删除原来的用户组
        $this::where('uid',$user_id)->delete();
        if (!empty($group_id)) {
            //添加新的用户组
            foreach ($group_id as $v) {
                if ($this::create(['group_id'=>$v, 'uid'=>$user_id])===false){
                    $this->error='系统异常';
                    return false;
                }
            }
        }
        return true;
    }
}