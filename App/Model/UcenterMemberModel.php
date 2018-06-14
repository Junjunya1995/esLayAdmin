<?php
/**
 * Created by PhpStorm.
 * User: wzj
 * Date: 2018/6/7
 * Time: 15:16
 */

namespace App\Model;

use think\Model;

class UcenterMemberModel extends Model
{

    protected $error;
    protected $insert             = ['status' => 1, 'username', 'reg_ip'];
    protected $autoWriteTimestamp = true;
    protected $createTime         = 'reg_time';
    protected $update             = ['last_login_time', 'last_login_ip'];


    /**
     * 用户登录认证
     * @param  integer $type 用户名类型 （1-用户名，2-邮箱，3-手机，4-UID）
     * @return integer           登录成功-用户ID，登录失败-错误编号
     */
    public function login($data)
    {
        //TODO :暂时只支持用户名登入
        $type = 1;
        switch ($type) {
            case 1:
                $field = 'username';
                break;
            case 2:
                $field = 'email';
                break;
            case 3:
                $field = 'mobile';
                break;
            case 4:
                $field = 'id';
                break;
            default:
                return 0; //参数错误
        }

        /* 获取用户数据 */
        $user = $this::get(function ($query) use ($field, $data) {
            $query->where($field, $data['username'])->field('id,status,password');
        });
        if (empty($user) || (int)$user->status !== 1) {
            return -1; //用户不存在或被禁用
        }
        /* 验证用户密码 */
        if (ucenter_md5($data['password']) !== $user->password) {
            return -2; //密码错误
        }
        $this->updateLogin($user->id); //更新用户登录信息
        return $user->id; //登录成功，返回用户ID
    }

    /**
     * 更新用户登录信息
     * @param  integer $uid 用户ID
     */
    protected function updateLogin($uid)
    {
        $this::update(['id' => $uid]);
    }

    /**
     * 注册一个新用户
     * @return int 注册成功-用户信息，注册失败-错误编号
     * @internal param array $data 用户注册信息
     */
    public function register()
    {
        $data     = Request::post();
        $validate = App::validate('UcenterMember');
        if (!$validate->check($data)) {
            $this->error = $validate->getError(); // 验证失败 输出错误信息
            return false;
        }
        unset($data['repassword']);
        $data['password'] = ucenter_md5($data['password']);//系统加密
        /* 添加用户 */
        $object = $this::create($data);
        return $object ? $object->toArray() : false;
    }

    /**
     * 更新用户信息
     * @param int    $uid 用户id
     * @param string $password 密码，用来验证
     * @param array  $tmp_data 修改的字段临时数组
     * @return true 修改成功，false 修改失败
     * @author huajie <banhuajie@163.com>
     */
    public function updateUserFields(int $uid, string $password, array $tmp_data = [])
    {
        if (empty($uid) || empty($password) || empty($tmp_data)) {
            $this->error = '参数错误！';
            return false;
        }

        //更新前检查用户密码
        if (!$this->verifyUser($uid, $password)) {
            $this->error = '验证出错：密码不正确！';
            return false;
        }
        //更新用户信息
        $validate = App::validate('UcenterMember');
        if (!$validate->scene('edit')->check($tmp_data)) {
            $this->error= $validate->getError();
            return false;
        }
        $data = [
            'id' => $uid,
            'password' => ucenter_md5($tmp_data['password']) //系统加密
        ];
        return $this::update($data);

    }

    /**
     * 验证用户密码
     * @param int    $uid 用户id
     * @param string $password_in 密码
     * @return true 验证成功，false 验证失败
     * @author huajie <banhuajie@163.com>
     */
    protected function verifyUser(int $uid, string $password_in)
    {
        $object = $this::get(function ($query)use($uid){
            $query->where('id', $uid)->field('password');
        });

        if ($object && ucenter_md5($password_in) === $object->password) {
            return true;
        }
        return false;
    }

    /**
     * 返回模型的错误信息
     * @access public
     * @return string|array
     */
    public function getError()
    {
        return $this->error;
    }

    /*===============修改器===============*/

    /**
     * 用户名转为小写
     * @author staitc7 <static7@qq.com>
     * @param $value
     * @return string
     */
    protected function setUsernameAttr($value)
    {
        return strtolower($value);
    }

    /**
     * 获取ip
     * @author staitc7 <static7@qq.com>
     */
    protected function setRegIpAttr()
    {
        //return Request::ip(1);
    }


    /**
     * 最后登录ip
     * @author staitc7 <static7@qq.com>
     */
    protected function setLastLoginIpAttr()
    {
        //return Request::ip(1);
    }

    /**
     * 最后登录时间
     * @author staitc7 <static7@qq.com>
     */
    protected function setLastLoginTimeAttr()
    {
        return time();
    }
}