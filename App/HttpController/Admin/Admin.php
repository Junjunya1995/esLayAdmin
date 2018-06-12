<?php
/**
 * Created by PhpStorm.
 * User: LWD
 * Date: 2018/6/4
 * Time: 23:43
 */

namespace App\HttpController\Admin;


use EasySwoole\Config;
use Lib\ControllerEX;
use think\Db;
use think\db\exception\ModelNotFoundException;
use think\Model;
use think\Template;

class Admin extends ControllerEX
{

    private $models = [];
    public function index()
    {
        return;
    }

    /**
     * 相当于构造函数
     * @param $action
     * @return bool|null|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
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

        if ($this->requestex()->isGet()) {
            $this->assign('systemMenus', $this->getMenus());
        }

        return true;

    }
    protected function isLogin()
    {
        $user = $this->session()->get('user_auth');
        if (empty($user)) {
            return 0;
        }
        if (isset($user['hotelid'])) { return  $user['hotelid'];}
        return $this->session()->get('user_auth_sign') == data_auth_sign((array)$user) ? (int) $user['uid'] : 0;
    }

    /**
     * @return bool|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getMenus()
    {
        if ($this->requestex()->isAjax()) { //ajax 跳过
            return false;
        }


        $menus = $this->session()->get('admin_meun_list');
        if ($menus) {
            //return $menus;
        }
        $controller = strtolower($this->getControllerName());//当前的控制器名
        $action = strtolower($this->getActionName());//当前的操作名
        $url = "{$controller}/{$action}";
        $where = [
            ['pid', '=', 0],
            ['hide', '=', 0],
            ['status', '<>', -1],
            ['is_dev', '=', 0]
        ];
        $menus['main'] = Db::name("menu")->where($where)->order('sort asc')
            ->field('id,title,url')->select(); // 获取主菜单
        $menus['child'] = []; //设置子节点
        foreach ($menus['main'] as $key => $item) {
            $item['url'] = strtolower($item['url']);
            // 判断主菜单权限
//            if (!UserInfo::isAdmin() && !$this->checkRule("{$module}/{$item['url']}", $this->app->config->get('config.auth_rule.rule_main'), null)) {
//                unset($menus['main'][$key]);
//                continue; //继续循环
//            }
            $url  == $item['url'] ? $menus['main'][$key]['class'] = 'layui-this' : null;
        }
        $map = [
            ['pid', '<>', 0],
            ['hide', '=', 0],
            ['status', '<>', -1],
            ['url' ,'=', $url]
        ]; // 查找当前子菜单
        $pid = Db::name("menu")->where($map)->value('pid');

        if ($pid) {
            $tmp_pid = Db::name("menu")->field('id,pid')->find($pid);
            $nav = $tmp_pid['pid'] ? Db::name("menu")->field('id,pid')->find($tmp_pid['pid']) : $tmp_pid; // 查找当前主菜单
            foreach ($menus['main'] as $key => $item) {
                if ((int)$item['id'] === (int)$nav['id']) {// 获取当前主菜单的子菜单项
                    $menus['main'][$key]['class'] = 'layui-this';
                    $groups = Db::name("menu")->where([['group','<>', ''], ['pid' ,'=', $item['id']]])->distinct(true)->column("group"); //生成child树
                    $second_urls = Db::name("menu")->where('pid',$item['id'])->field('id,url')->select() ?: []; //获取二级分类的合法url
                    $to_check_urls = $this->toCheckUrl($second_urls); // 检测菜单权限
                    foreach ($groups as $g) {// 按照分组生成子菜单树
                        $where=[['pid','=',$item['id']],['group','=',$g]];
                        //if (isset($to_check_urls) && !empty($to_check_urls)) {
                            $where[] = ['url','in', $to_check_urls];
                        //}
                        $menuList = Db::name("menu")->where($where)->field('id,pid,title,url,tip')->order('sort asc')->select();
                        $menus['child'][$g] = list_to_tree($menuList, 'id', 'pid', 'operater', $item['id']);
                    }
                }
            }
        }

        $this->session()->set('admin_meun_list', $menus);

        return $menus;

    }

    /**
     * 非超级管理员的权限检测
     * @param array $second_urls
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */
    private function toCheckUrl(array $second_urls = []) {
//        // 检测菜单权限
//        if (empty(UserInfo::userId())) {
//            return null;
//        }
        //$module = $this->app->request->module();
        $to_check_urls = [];
        if (empty($second_urls)){
            return null;
        }
        foreach ($second_urls as $key => $to_check_url) {
                $rule = $to_check_url['url'];
            //if ($this->checkRule($rule, $this->app->config->get('config.auth_rule.rule_url'), null)) {
                $to_check_urls[] = $to_check_url['url'];
            //}
        }
        return empty($to_check_urls) ? null : $to_check_urls;
    }
    /**
     * 权限检测
     * @param string $rule 检测的规则
     * @param null $type
     * @param string $mode check模式
     * @return bool
     * @author 朱亚杰  <xcoolcc@gmail.com>
     */
    final private function checkRule($rule, $type = null, $mode = 'url') {
        static $Auth_static = null;
        $Auth = $Auth_static ?? new Auth();
        $type = $type ? $type : $this->app->config->get('config.auth_rule.rule_url');
        if (!$Auth->check($rule, UserInfo::userId(), $type, $mode)) {
            return false;
        }
        return true;
    }

    public function actionNotFound($action): void
    {
        parent::actionNotFound($action); // TODO: Change the autogenerated stub
        $this->dump('404');

    }

    /**
     * 单例实例化model
     * @param        $model
     * @param string $namespace
     * @return Model
     * @throws ModelNotFoundException
     */
    protected function model($model, $namespace = 'App\Models'):Model {
        if (empty($this->models[$model])) {
            $model = $namespace . '\\' . $model;
            if (class_exists($model)) {
                $this->models[$model] = new $model();
            } else {
                throw new ModelNotFoundException("类没有找到");
            }
        }

        return $this->models[$model];
    }

    /**
     * layui 专用返回数据格式
     * @param array|null  $data 返回的数据
     * @param int         $code 状态码
     * @param null|string $msg 状态信息
     * @return mixed
     */
    protected  function layuiJson(array $data = [], int $code = 0, string $msg = ''){
        if (empty($data) || !isset($data['total']) || empty($data['data'])) {
            $data = ['code' => 1, 'msg' => '暂时没有数据', 'count' => 0, 'data' => ''];
        } else {
            $data = ['code' => $code, 'msg' =>$msg, 'count' => $data['total'], 'data' => $data['data']];
        }
        $this->response()->write(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        $this->response()->withHeader('Content-type','application/json;charset=utf-8');
    }
}