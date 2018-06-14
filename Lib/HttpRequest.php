<?php
/**
 * Created by PhpStorm.
 * User: wzj
 * Date: 2018/6/10
 * Time: 23:57
 */

namespace Lib;


use EasySwoole\Core\Http\Request;

class HttpRequest
{
    private  $request = null;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getMethod() {
        return $this->request->getServerParams()['request_method'];
    }

    public function isPost() {
        return $this->getMethod() === 'POST' ? true : false;
    }

    public function isGet() {
        return $this->getMethod() === 'GET' ? true : false;
    }

    public function isAjax() {
        $ajax = $this->request->getHeader('x-requested-with');
        if(empty($ajax)) {
            return false;
        }
        return $ajax[0] === 'XMLHttpRequest';
    }

    public function getIp() {
        $ip = $this->request->getHeader('x-real-ip');
        return  count($ip)  ? $ip[0] : '0.0.0.0';
    }
}