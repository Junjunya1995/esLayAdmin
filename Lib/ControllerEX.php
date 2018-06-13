<?php
/**
 * Created by PhpStorm.
 * User: LWD
 * Date: 2018/6/4
 * Time: 23:43
 */

namespace Lib;


use EasySwoole\Config;
use EasySwoole\Core\Http\AbstractInterface\Controller;
use EasySwoole\Core\Http\Request;
use EasySwoole\Core\Http\Response;
use EasySwoole\Core\Http\Session\Session;
use think\Db;
use think\Template;

class ControllerEX extends Controller
{

    protected  $view;
    private $session = null;

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
    

    public function index()
    {
       return;
    }

    /**
     * 输出模板到页面
     * @param  string|null $template 模板文件
     * @param array        $vars     模板变量值
     * @param array        $config   额外的渲染配置
     * @author : wzj
     */
    public function fetch($template = '', $vars = [], $config = [])
    {
        if (empty($template)) { //未指定文件路径时
            $template = $this->getControllerName() . '/' .$this->getActionName();
        }
        ob_start();
        $this->view->fetch($template, $vars, $config);
        $content = ob_get_clean();
        $this->response()->write($content);
    }

    /**
     * 模板赋值
     * @param $name
     * @param $val
     */
    protected function assign ($name, $val) {
        $this->view->assign($name, $val);
    }

    /**
     * 页面输出变量
     * @param $val
     */
    protected function dump($val) {
        $this->response()->write(var_export($val, true));
    }



    //用来返回错误信息（json）
    public function error($message){
        if(!$this->response()->isEndResponse()){
            $data = Array(
                "code"   => 0 ,
                "msg"    => $message
            );
            $this->response()->write(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type','application/json;charset=utf-8');
            $this->response()->withStatus(200);
            return true;
        }else{
            trigger_error("response has end");
            return false;
        }
    }

    //用来返回成功信息（json）
    public function success($result = '' , $url= '', $code = 1){
        if(!$this->response()->isEndResponse()){
            $data = Array(
                "code"=>$code,
                "msg"=>$result,
                "url"   => $url
            );
            $this->response()->write(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type','application/json;charset=utf-8');
            $this->response()->withStatus(200);
            return true;
        }else{
            trigger_error("response has end");
            return false;
        }
    }


    /**
     * 重写session 函数
     * @return \EasySwoole\Core\Http\Session\Session|void
     */
    public function session(): Session
    {
        if($this->session == null){
            $this->session = new Session($this->request(),$this->response());
        }
        if($this->session->isStart() === false) {
            $this->session->sessionStart();
        }
        return $this->session;
    }


    /**
     * 扩展了 原本的request对象的方法
     * @return RequestEX
     */
    protected  function  requestex(): RequestEX
    {
        return new RequestEX(parent::request());
    }

    protected function getControllerName(): string
    {
        return  str_replace('\\', '/', explode('App\HttpController\\',static::class)[1]);
    }
}