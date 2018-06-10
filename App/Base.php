<?php
/**
 * Created by PhpStorm.
 * User: LWD
 * Date: 2018/6/4
 * Time: 23:43
 */

namespace App;


use App\Traits\ControllerTrait;
use App\Traits\RquestTrait;
use App\Traits\ViewTrait;
use EasySwoole\Config;
use EasySwoole\Core\Http\AbstractInterface\Controller;
use EasySwoole\Core\Http\Request;
use EasySwoole\Core\Http\Response;
use EasySwoole\Core\Http\Session\Session;
use think\Db;
use think\Template;

class Base extends Controller
{
    use ControllerTrait;
    use RquestTrait;

    protected $view;


    /**
     * 相对与以前的构造函数
     * @param $action
     * @return bool|null|void
     */
    protected function onRequest($action):?bool {

        if ($this->isLogin() === 0) {
            $this->response()->redirect('/admin/login/index');
        }

        $this->view = new Template();
        $tempPath   = Config::getInstance()->getConf('TEMP_DIR');     # 临时文件目录
        $tempConf   = Config::getInstance()->getConf('TEMPLATE');
        $this->view->config([
            'cache_path' => "{$tempPath}/templates_c/",               # 模板编译目录
        ]);
        $this->view->config($tempConf);

        if ($this->isGet()) {
            $this->assign('systemMenus', $this->getMenus());
        }

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
            $template = str_replace('\\', '/', explode('App\HttpController\\',static::class)[1]) . '/' .$this->getActionName();
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
}