<?php
/*
 * Created by PhpStorm.
 * User: wzj
 * Date: 2018/6/14
 * Time: 23:43
 */

namespace App\HttpController\Admin;

class Personal extends  Admin
{

    /**
     * 修改昵称初始化
     * @author huajie <banhuajie@163.com>
     */
    public function updateNickname() {
        return $this->fetch('', [
            'nickname'=>$this->getUser()['nickname'],
            'metaTitle' => '修改昵称'
        ]);
    }

    /**
     * 修改昵称提交
     * @param string $password 密码
     * @param string $nickname 昵称
     * @author huajie <banhuajie@163.com>
     */
    public function submitNickname($password = null, $nickname = null)
    {
        empty($password) && $this->error('请输入密码');
        empty($nickname) && $this->error('请输入昵称');
        $uid = $this->app->model('UcenterMember', 'models')->login(UserInfo::userId(), $password, 4); //密码验证
        if ($uid == -2) {
            return $this->error('密码不正确');
        }
        $Member = $this->app->model('Member');
        $data   = $Member->renew(['nickname' => $nickname, 'uid' => UserInfo::userId()]);
        if ($data === false) {
            return $this->error($Member->getError());
        }
        $user             = $this->app->session->get('user_auth');
        $user['username'] = $data['nickname'];
        $this->app->session->set('user_auth', $user);
        $this->app->session->set('user_auth_sign', data_auth_sign($user));
        return $this->success('修改昵称成功！');
    }

    /**
     * 修改密码初始化
     * @author huajie <banhuajie@163.com>
     */
    public function updatePassword() {
        return $this->fetch('', ['metaTitle'=>'修改密码']);
    }

    /**
     * 修改密码提交
     */
    public function submitPassword() {
        $data = $this->requestex()->param();
        $old_password = $data['old_password'] ;
        $password = $data['password'] ;
        $repassword = $data['repassword'] ;
        empty($old_password) && $this->error('请输入原密码');
        empty($password) && $this->error('请输入新密码');
        empty($repassword) && $this->error('请输入确认密码');
        $UcenterMember = $this->model('UcenterMember');
        $res = $UcenterMember->updateUserFields($this->isLogin(), $old_password, [
            'repassword'=>$repassword,
            'password' => $password
        ]);
        if ($res===false) {
            return $this->error($UcenterMember->getError());
        }
        $this->session()->destroy();
        return $this->success('修改密码成功,请重新登录 ');
    }

    /**
     * 用户头像
     * @author staitc7 <static7@qq.com>
     */
    public function portrait() {
        return $this->fetch('',['metaTitle' => '设置头像']);
    }

    /**
     * 图片上传
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */
    public function submitPortrait()
    {
        $data = PictureUpload::upload('UserPicture');
        if ($data===false){
            $this->error(PictureUpload::getError());
        }
        //更新头像
        Db::name('Member')->update(['uid'=>UserInfo::userId(), 'portrait'=>$data['id']]);
        $this->app->cookie->delete("user_".UserInfo::userId(),'portrait_');//删除旧头像
        return $this->success('上传成功!', '', $data);
    }

}