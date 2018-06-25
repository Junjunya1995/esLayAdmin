<?php
/**
 * Created by PhpStorm.
 * User: wzj
 * Date: 2018/6/4
 * Time: 23:43
 */

namespace App\HttpController\Admin;


use Lib\Auth;
use Lib\HttpController;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;

class Admin extends HttpController
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
            return false;
        }
        parent::onRequest($action);
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

    protected function getUser() {
        if (0 === $this->isLogin()) {
            return false;
        }
        return $this->session()->get('user_info');
    }

    protected function isAdmin () :bool {
        return $this->getUser()['uid'] == 1;
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
        $controller = strtolower($this->getControllerName());//当前的控制器名
        $action = strtolower($this->getActionName());//当前的操作名
        $url = "/{$controller}/{$action}";
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
            if (!$this->isAdmin() && !$this->checkRule($item['url'], 2, null)) {
                unset($menus['main'][$key]);
                continue; //继续循环
            }
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
                        $where=[['pid','=',$item['id']], ['group','=',$g], ['status', '<>', -1]];
                        if (isset($to_check_urls) && !empty($to_check_urls)) {
                            $where[] = ['url','in', $to_check_urls];
                        }
                        $menuList = Db::name("menu")->where($where)->field('id,pid,title,url,tip')->order('sort asc')->select();
                        $menus['child'][$g] = list_to_tree($menuList, 'id', 'pid', 'operater', $item['id']);
                        if (count($menus['child'][$g]) == 0) {
                            unset($menus['child'][$g]);
                        }
                    }
                }
            }
        }
        return $menus;

    }

    /**
     * 非超级管理员的权限检测
     * @param array $second_urls
     * @return mixed
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @author staitc7 <static7@qq.com>
     */
    private function toCheckUrl(array $second_urls = []) {
        $to_check_urls = [];
        if (empty($second_urls)){
            return null;
        }
        foreach ($second_urls as $key => $to_check_url) {
                $rule = $to_check_url['url'];
            if ($this->checkRule($rule, 1, null)) {
                $to_check_urls[] = $to_check_url['url'];
            }
        }
        return empty($to_check_urls) ? null : $to_check_urls;
    }

    /**
     * 权限检测
     * @param string $rule 检测的规则
     * @param null   $type
     * @param string $mode check模式
     * @return bool
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     */
    final private function checkRule($rule, $type = null, $mode = 'url') {
        static $Auth_static = null;
        $Auth = $Auth_static ?? new Auth($this->requestex(), $this->session());
        $type = $type ? $type : 1;
        if (!$Auth->check($rule, $this->isLogin(), $type, $mode)) {
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
    protected function model($model, $namespace = 'App\Model\Admin'):Model {
        $namespaceInt = 'App\Model';

        if (class_exists($namespace . '\\' . $model)) {
            $model = $namespace.'\\'.$model;
        } else  if (class_exists($namespaceInt.'\\'.$model)){
            $model = $namespaceInt.'\\'.$model;
        } else {
            throw new ModelNotFoundException("类没有找到");
        }
        if (empty($this->models[$model])) {
            $this->models[$model] = new $model();
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


    /**
     * 通用排序更新
     * @param int $id 菜单ID
     * @param int $sort 排序
     * @author staitc7 <static7@qq.com>
     */
    public function currentSort($id = 0, $sort = null) {
        (int) $id || $this->error('参数错误');
        is_numeric((int) $sort) || $this->error('排序非数字');
        $info =$this->app->model($this->app->request->controller())->setStatus([['id', '=', $id]], ['sort' => (int) $sort]);
        return $info !== false ?
            $this->success('排序更新成功') :
            $this->error('排序更新失败');
    }


    /**
     * 通用单条数据状态修改
     * @return bool|void
     * @throws ModelNotFoundException
     * @internal param ids $int 数据条件
     */
    public function setStatus() {
        $data = $this->request()->getParsedBody();

        $value = $data['value'];
        $ids = $data['ids'];
        empty($ids) && $this->error('请选择要操作的数据');
        is_numeric((int) $value) || $this->error('参数错误');
        $controller  = $this->getControllerName();
        $model = explode('Admin/', $controller)[1];

        $info = $this->model($model)
            ->setStatus([['id','in', $ids]], ['status' => $value]);
        $info !== false ?
            $this->success($value == -1 ? '删除成功' : '更新成功') :
            $this->error($value == -1 ? '删除失败' : '更新失败');
    }

    /**
     * 通用批量数据更新
     * @param int $value 状态
     * @author staitc7 <static7@qq.com>
     */
    public function batchUpdate($value = null) {
        $ids = $this->requestex()->post();
        empty($ids['ids']) && $this->error('请选择要操作的数据');
        is_numeric((int) $value) || $this->error('参数错误');
        $info = $this->app->model($this->app->request->controller())
            ->setStatus([['id','in', $ids['ids']]], ['status' => $value]);
        return $info !== false ?
            $this->success($value == -1 ? '删除成功' : '更新成功') :
            $this->error($value == -1 ? '删除失败' : '更新失败');
    }

    public function fetch($template = '', $vars = [], $config = [])
    {
        $this->assign('userinfo', $this->getUser());
        try {
            $this->assign('systemMenus', $this->getMenus());
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
        parent::fetch($template, $vars, $config);
    }
}