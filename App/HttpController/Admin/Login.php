<?php
/**
 * Created by PhpStorm.
 * User: LWD
 * Date: 2018/6/6
 * Time: 23:24
 */

namespace App\HttpController\Admin;


use App\Models\MemberModel;
use App\Models\UcenterMemberModel;
use EasySwoole\Config;
use Lib\ControllerEX;
use think\Template;

class Login extends ControllerEX
{

    /**
     * 相当于构造函数
     * @param $action
     * @return bool|null|void
     */
    protected function onRequest($action):?bool {

        $this->view = new Template();
        $tempPath   = Config::getInstance()->getConf('TEMP_DIR');     # 临时文件目录
        $tempConf   = Config::getInstance()->getConf('TEMPLATE');
        $this->view->config([
            'cache_path' => "{$tempPath}/templates_c/",               # 模板编译目录
        ]);
        $this->view->config($tempConf);

        return true;

    }
    /**
     * 登入页面
     */
    public function index() {
        $this->fetch();
    }

    /**
     * 处理登入请求
     * @return int
     */
    public function login()
    {
        $UcenterMemberModel = new UcenterMemberModel();

        //判断是否ajax登录
        $this->requestex()->isPost() || $this->error('非法请求');
        $data = $this->request()->getParsedBody();
        $data['ip'] = $this->requestex()->getIp();

        $user_id = $UcenterMemberModel->login($data);

        //登录失败
       if ((int)$user_id < 0) {
            $this->error('登入失败');
            return 0;
        }
        //更新用户信息
        $Member = MemberModel::create();
        $info   = $Member->login($user_id, $this->requestex()->getIp());
        if ($info === false) {
            $this->error($Member->getError());
            return 0;
        }
//        //处理用户信息
        $this->autoLogin($info);
        $this->success('登录成功', '/admin/Index/index');

    }

    public function autoLogin(array $data)
    {
        /* 记录登录SESSION和COOKIES */
        $auth = [
            'uid' => $data['uid'],
            'username' => $data['nickname'],
            'last_login_time' => $data['last_login_time']
        ];
        //缓存用户信息
        $this->session()->set('user_info', $data);
        $this->session()->set('user_auth', $auth);
        $this->session()->set('user_auth_sign', data_auth_sign((array)$auth));
//        //记录行为
//        $param = [
//            'action' => 'user_login',
//            'model' => __CLASS__,
//            'record_id' => $data['uid'],
//            'user_id'=>$data['uid']
//        ];
        return true;
    }
}