<?php

namespace App\HttpController\Admin;

use App\Facade\Tree;
use EasySwoole\Core\Http\Request;
use EasySwoole\Core\Http\Response;
use EasySwoole\VerifyCode\VerifyCode;
use EasySwoole\VerifyCode\Conf;
use App\Base;

/**
 * Class Index
 * @package App\HttpController
 */
class Index extends Admin
{


    public function index()
    {
        $this->dump($this->isAdmin());
        return $this->fetch();
    }

    /**
     *
     */
    public function VerifyCode()
    {
        $VCode = new VerifyCode();
        // 随机生成验证码
        $Code = $VCode->DrawCode();
        $this->session()->set('verify',$Code->getImageCode());
        $this->response()->withHeader('Content-Type','image/png')->write($Code->getImageByte());
    }

    /**
     * 首页方法
     * @author : evalor <master@evalor.cn>
     */
    public function show()
    {

    }
}
