<?php
/**
 * Created by PhpStorm.
 * User: LWD
 * Date: 2018/6/8
 * Time: 14:29
 */

namespace App\Model;


use App\Traits\ModelTrait;
use think\Model;

class MemberModel extends Model
{


    use ModelTrait;
    protected $pk = 'uid';
    protected $autoWriteTimestamp = false;

    /**
     * 登录指定用户
     * @param  integer $user_id 用户ID
     * @param          $ip
     * @return boolean      ture-登录成功，false-登录失败
     */
    public function login($user_id = 0, $ip) {
        /* 检测是否在当前应用注册 */
        $object = $this::get(function($query)use($user_id) {
            $query->where('uid', $user_id);
        });
        if (!$object || $object->status != 1) {
            $this->error = '用户不存在或已被禁用！'; //应用级别禁用
            return false;
        }
        /* 更新登录信息 */
        $object->login++;
        $object->last_login_time= time();
        $object->last_login_ips = $ip;
        $object->save();
        return $object ? $object->toArray() : false;
    }

    /**
     * 条件查询权限用户
     * @param array $map 查询条件
     * @param string $field 查询的字段
     * @return array|null
     * @author staitc7 <static7@qq.com>
     */

    public function oneUser(array $map = [], string $field = "*") {
        $map=array_merge(['status'=>1],$map);
        $object = $this::get(function($query)use($map, $field) {
            $query->where($map)->field($field);
        });
        return $object ? $object->toArray() : false;
    }

    /**
     * 添加用户
     * @author staitc7 <static7@qq.com>
     */

    public function userAdd() {
        $UcenterMember = App::model('UcenterMember', 'model');
        $register_data = $UcenterMember->register();
        if ($UcenterMember->getError()) {
            $this->error= $UcenterMember->getError();
            return false;
        }

        $object = $this::create([
            'uid' => $register_data['id'],
            'nickname' => $register_data['username'],
            'reg_ip' => $register_data['reg_ip'],
            'reg_time' => strtotime($register_data['reg_time']),
            'status' => $register_data['status'],
        ]);
        return $object ? $object->toArray() : false;
    }

    /**
     * 查询用户是否存在
     * @author staitc7 <static7@qq.com>
     * @param int $user_id 用户id
     * @return array
     * @throws \think\exception\DbException
     */

    public function userId(int $user_id = 0): array {
        $object = $this::get(function($query)use($user_id) {
            $query->where('uid', $user_id);
        });
        return $object ? $object->toArray() : [];
    }

    /*================获取器================*/

    /**
     * 最后登录时间转换
     * @author staitc7 <static7@qq.com>
     * @param $value 修改的值
     * @return string
     */

    public function getLastLoginTimeAttr($value) {
        return $value ? date('Y-m-d H:i:s', $value) : null;
    }

    /**
     * 最后登录IP转换
     * @author staitc7 <static7@qq.com>
     * @param $value 修改的值
     * @return string
     */

    public function getLastLoginIpAttr($value) {
        return $value ? long2ip($value) : null;
    }

//    public function getPortraitAttr($value) {
//
//        return $value;
//    }
}