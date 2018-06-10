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
use think\Template;

class Admin extends ControllerEX
{

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
    public function getMenus()
    {
        if ($this->requestex()->isAjax()) { //ajax 跳过
            return false;
        }
//        $controller = strtolower($this->app->request->controller());//当前的控制器名
//        if ($controller=="admin"){
//            return $this->error("未授权访问");
//        }
        $menus = $this->session()->get('admin_meun_list');
        if ($menus) {
            return $menus;
        }
//        $module = strtolower($this->app->request->module());//当前的模块名
//        $action = strtolower($this->app->request->action());//当前的操作名
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
            //"{$controller}/{$action}" == $item['url'] ? $menus['main'][$key]['class'] = 'layui-this' : null;
        }
        $map = [
            ['pid', '<>', 0],
            ['hide', '=', 0],
            ['status', '<>', -1],
            //['url' ,'=', ""]
        ]; // 查找当前子菜单
//        $pid = Db::name("menu")->where($map)->value('pid');
//        if ($pid) {
//            $tmp_pid = Db::name("menu")->field('id,pid')->find($pid);
//            $nav = $tmp_pid['pid'] ? Db::name("menu")->field('id,pid')->find($tmp_pid['pid']) : $tmp_pid; // 查找当前主菜单
//            foreach ($menus['main'] as $key => $item) {
//                if ((int)$item['id'] === (int)$nav['id']) {// 获取当前主菜单的子菜单项
//                    $menus['main'][$key]['class'] = 'layui-this';
//                    $groups = Db::name("menu")->where([['group','<>', ''], ['pid' ,'=', $item['id']]])->distinct(true)->column("group"); //生成child树
//                    $second_urls = Db::name("menu")->where('pid',$item['id'])->field('id,url')->select() ?: []; //获取二级分类的合法url
//                    $to_check_urls = $this->toCheckUrl($second_urls); // 检测菜单权限
//                    foreach ($groups as $g) {// 按照分组生成子菜单树
//                        $where=[['pid','=',$item['id']],['group','=',$g]];
//                        if (isset($to_check_urls) && !empty($to_check_urls)) {
//                            $where[] = ['url','in', $to_check_urls];
//                        }
//                        $menuList = Db::name("menu")->where($where)->field('id,pid,title,url,tip')->order('sort asc')->select();
//                        $menus['child'][$g] = list_to_tree($menuList, 'id', 'pid', 'operater', $item['id']);
//                    }
//                }
//            }
//        }

        $this->session()->set('admin_meun_list', $menus);

        return $menus;

    }
}