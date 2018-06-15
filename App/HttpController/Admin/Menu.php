<?php
/**
 * Description of Menu.php.
 * User: static7 <static7@qq.com>
 * Date: 2017-08-03 17:32
 */

namespace  App\HttpController\Admin;


use Lib\Tree;
use think\db\exception\ModelNotFoundException;

class Menu extends  Admin {

    /**
     * 菜单首页
     * @param int $pid
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */

    public function index()
    {
        $pid = $this->request()->getQueryParam('pid') ?? 0;
        $father = null;
        try {
            $father = $this->model('MenuModel')->father($pid);
        } catch (ModelNotFoundException $e) {
        }
        $this->assign([
            'pid' => $pid,
            'father' => $father ?: null,
            'metaTitle' => '菜单列表'
        ]);
        $this->fetch();

    }

    /**
     * 菜单列表
     * @return mixed
     */
    public function menuJson()
    {
        $data = $this->request()->getParsedBody();
        $map  = [
            ['status', '<>', -1],
            ['pid', '=', (int)$data['pid'] ?: 0]
        ];
        try {
            $data = $this->model('MenuModel')
                ->listsJson($map, null, 'sort asc,id asc', (int)$data['page'] ?? 1, $data['limit'] ?? 10);
        } catch (ModelNotFoundException $e) {

        }
        return $this->layuiJson($data);
    }

    /**
     * 公用的更新方法
     * @param null   $ids
     * @param null   $value
     * @param string $field
     * @author staitc7 <static7@qq.com>
     * @return bool
     */

    public function toogle() {
        $data = $this->request()->getBody();
        $ids = $data['ids'];
        $value = $data['value'];
        $field = $data['field'] ?? 'hide';
        empty($ids) && $this->error('请选择要操作的数据');
        is_numeric((int)$value) || $this->error('参数错误');
        $key = ((string)$field == 'hide') ? 'hide' : 'is_dev';
        $info = $this->model('MenuModel')->setStatus([['id','in', $ids]], [$key => $value]);
        if ($info === false) {
            return $this->error($value == -1 ? '删除失败' : '更新失败');
        }
        $this->session()->set('menu', null);
        return $this->success($value == -1 ? '删除成功' : '更新成功');
    }

    /**
     * 添加菜单
     * @return mixed
     * @throws ModelNotFoundException
     */

    public function add() {
        $pid = $this->request()->getQueryParam('pid') ?? 0;

        $menu_list_all =$this->model('MenuModel')->menuListAll(); //获取所有的菜单
        if (is_array($menu_list_all)){
            $tree=Tree::toFormatTree($menu_list_all);
        }
        $this->fetch('Admin/Menu/edit',['menus'=>$tree,'pid' => $pid,'metaTitle' => '添加菜单']);
    }

    /**
     * 用户更新或者添加菜单
     */
    public function renew() {
        $Menu =$this->model('MenuModel');
        $info = $Menu->renew($this->request()->getParsedBody());
        if ($info===false) {
            return $this->error($Menu->getError());
        }
        $this->session()->set('menu', null);
        return $this->success('操作成功', '/admin/Menu/index?pid=' . ($info['pid'] ?: 0));
    }

    /**
     * 菜单详情
     * @return mixed
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     */

    public function edit()
    {
        $id = $this->request()->getQueryParam('id');
        $Menu = $this->model('MenuModel');
        if ((int)$id > 0) {
            $info = $Menu->edit((int)$id);
        }
        $menu_list_all = $Menu->menuListAll(); //获取所有的菜单
        if (is_array($menu_list_all)) {
            $tree = Tree::toFormatTree($menu_list_all);
        }

        $this->fetch('',['info' => $info ?? '', 'menus' => $tree ?? '', 'metaTitle' => '菜单详情']);
    }

//    /**
//     * 返回后台节点数据
//     * @param boolean $tree 是否返回多维数组结构(生成菜单时用到),为false返回一维数组(生成权限节点时用到)
//     *                      注意,返回的主菜单节点数组中有'controller'元素,以供区分子节点和主节点
//     * @return array
//     * @throws ModelNotFoundException
//     * @author 朱亚杰 <xcoolcc@gmail.com>
//     */
//    final public function returnNodes($tree = true) {
//        static $tree_nodes = [];
//        if ($tree && !empty($tree_nodes[(int)$tree])) {
//            return $tree_nodes[$tree];
//        }
//        $Menu = $this->model('MenuModel');
//        $model_name = $this->getControllerName(); //当前模块名称
//        if ($tree) {
//            $list = $Menu->menuField('id,pid,title,url,tip,hide');
//            foreach ($list as $key => $value) {
//                if (stripos($value['url'], $model_name) !== 0) {
//                    $list[$key]['url'] = "{$model_name}/{$value['url']}";
//                }
//            }
//            $nodes = list_to_tree($list, 'id', 'pid', 'operator', 0);
//            foreach ($nodes as $key => $value) {
//                if (!empty($value['operator'])) {
//                    $nodes[$key]['child'] = $value['operator'];
//                    unset($nodes[$key]['operator']);
//                }
//            }
//        } else {
//            $nodes = $Menu->menuField('title,url,tip,pid');
//            foreach ($nodes as $key => $value) {
//                if (stripos($value['url'], $model_name) !== 0) {
//                    $nodes[$key]['url'] = "{$model_name}/{$value['url']}";
//                }
//            }
//        }
//        $tree_nodes[(int)$tree] = $nodes;
//        return $nodes;
//    }
}