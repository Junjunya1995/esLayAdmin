<?php
/**
 * Description of UcenterMember.php.
 * User: static7 <static7@qq.com>
 * Date: 2017/9/20 22:20
 */

namespace App\Validates;


use Lib\Validate;

class UcenterMemberValidate extends Validate
{
    protected $rule    = [
        'username' => 'alphaDash|require|length:4,30|unique:ucenter_member,username',
        'password' => 'require|min:6',
        'repassword' => 'require|confirm:password',
        'email' => "unique:ucenter_member,email|email"
    ];
    protected $message = [
        'username.requier' => '用户名不能为空',
        'username.unique' => '用户名已经被注册',
        'username.length' => '用户名在4-20个字符之间',
        'username.alphaDash' => '用户名为字母和数字，下划线"_"及破折号"-"',
        'password.require' => '密码不能为空', 'password.min' => '密码最低6个字符',
        'repassword.require' => '确认密码不能为空',
        'repassword.confirm' => '两次密码不相符',
        'email' => '邮箱格式错误',
        'email.unique' => '邮箱已经被注册过',
    ];
    protected $scene = [
        'edit'  =>  ['password','repassword']
    ];

}