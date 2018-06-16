<?php
/**
 * Created by PhpStorm.
 * User: wzj
 * Date: 2018/6/10
 * Time: 23:57
 */

namespace Lib;


use EasySwoole\Core\Http\Request;

/**
 * Class HttpRequest
 * @package Lib
 */
class HttpRequest
{

    private  $request = null;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * 判断是否是POST 请求
     * @return bool
     */
    public function isPost() {
        return $this->getMethod() === 'POST';
    }

    /**
     * 判断是否是 GET 请求
     * @return bool
     */
    public function isGet() {
        return $this->getMethod() === 'GET';
    }

    /**
     * 判断是否是Ajax 请求
     * @return bool
     */
    public function isAjax() {
        $ajax = $this->request->getHeader('x-requested-with');
        if(empty($ajax)) {
            return false;
        }
        return $ajax[0] === 'XMLHttpRequest';
    }

    /**
     * 获取 客户端IP
     * @author wzj
     * @return string
     */
    public function getIp() {
        $ip = $this->request->getHeader('x-real-ip');
        return  count($ip)  ? $ip[0] : '0.0.0.0';
    }

    public function get($name = '') {
        return  $name == true ?  $this->request->getQueryParam($name) ?? null :
            $this->request->getQueryParams();
    }

    public function post($name = '') {
        return  $name == true ?  $this->request->getParsedBody()[$name] ?? null :
            $this->request->getParsedBody();
    }

    /**
     * 获取当前请求的参数
     * @param string $name    参数名
     * @param null   $default 默认值
     * @param string $filter  预处理函数
     * @author wzj
     * @return array|null
     */
    public function param($name = '', $default = null, $filter = '') {
        //合并请求参数
        $param = array_merge($this->request->getQueryParams(), $this->request->getParsedBody());

        if (!empty($name)) {
            if (!empty($filter)) {
                $param[$name] = $filter($param[$name]);
            }
            return $param[$name] ?? $default;
        }

        foreach ($param as $k => $v) {
            if (!empty($filter)) {
                $v = $filter($v);
            }
            $param[$k] = $v ?? $default;
        }
        return $param;
    }

    /**
     * 继承 EasySwoole\Core\Http\Request的所有方法
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args) {
        return call_user_func_array([$this->request, $method], $args);
    }
}