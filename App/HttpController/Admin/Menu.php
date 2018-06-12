<?php
/**
 * Description of Menu.php.
 * User: static7 <static7@qq.com>
 * Date: 2017-08-03 17:32
 */

namespace  App\HttpController\Admin;


use app\common\facade\Tree;

class Menu extends  Admin {

    /**
     * 菜单首页
     * @param int $pid
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */

    public function index($pid = 0)
    {
        $father = $this->model('MenuModel')->father($pid); //查询父级ID
//        return $this->setView([
//            'pid' => $pid,
//            'father' => $father ?: null,
//            'metaTitle' => '菜单列表'
//        ]);
        $this->assign('pid',$pid);
        $this->assign('father',$father);
        $this->fetch();

    }

    /**
     * 菜单列表
     * @author staitc7 <static7@qq.com>
     * @param int $pid 父级ID
     * @param int $page 页码
     * @param int $limit 限制条数
     * @return mixed
     */
    public function menuJson($pid=0,$page=1,$limit=10)
    {
        $map  = [
            ['status', '<>', -1],
            ['pid', '=', (int)$pid ?: 0]
        ];
        $data = $this->model('MenuModel')->listsJson($map, null, 'sort asc,id asc', (int)$page ?: 1, $limit);
        return $this->layuiJson($data);
    }

    /**
     * 公用的更新方法
     * @param null $ids
     * @param null $value
     * @param string $field
     * @author staitc7 <static7@qq.com>
     */

    public function toogle($ids = null, $value = null, $field = 'hide') {
        empty($ids) && $this->error('请选择要操作的数据');
        is_numeric((int)$value) || $this->error('参数错误');
        $key = ((string)$field == 'hide') ? 'hide' : 'is_dev';
        $info = $this->app->model('Menu')->setStatus([['id','in', $ids]], [$key => $value]);
        if ($info === false) {
            return $this->error($value == -1 ? '删除失败' : '更新失败');
        }
        $this->app->session->clear('menu');
        return $this->success($value == -1 ? '删除成功' : '更新成功');
    }

    /**
     * 添加菜单
     * @param int $pid 菜单ID
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */

    public function add($pid = 0) {
        $menu_list_all =$this->app->model('Menu')->menuListAll(); //获取所有的菜单
        if (is_array($menu_list_all)){
            $tree=Tree::toFormatTree($menu_list_all);
        }
        return $this->setView(['menus'=>$tree,'pid' => $pid,'metaTitle' => '添加菜单']);
    }

    /**
     * 用户更新或者添加菜单
     * @author staitc7 <static7@qq.com>
     */

    public function renew() {
        $Menu =$this->app->model('Menu');
        $info = $Menu->renew();
        if ($info===false) {
            return $this->error($Menu->getError());
        }
        $this->app->session->delete('menu');
        return $this->success('操作成功', $this->app->url->build('Menu/index', ['pid' => $info['pid'] ?: 0]));
    }

    /**
     * 菜单详情
     * @param int $id 菜单ID
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */

    public function edit($id = 0)
    {
        $Menu = $this->app->model('Menu');
        if ((int)$id > 0) {
            $info = $Menu->edit((int)$id);
        }
        $menu_list_all = $Menu->menuListAll(); //获取所有的菜单
        if (is_array($menu_list_all)) {
            $tree = Tree::toFormatTree($menu_list_all);
        }
        return $this->setView(['info' => $info, 'menus' => $tree, 'metaTitle' => '菜单详情']);
    }

    /**
     * 返回后台节点数据
     * @param boolean $tree 是否返回多维数组结构(生成菜单时用到),为false返回一维数组(生成权限节点时用到)
     * 注意,返回的主菜单节点数组中有'controller'元素,以供区分子节点和主节点
     * @author 朱亚杰 <xcoolcc@gmail.com>
     * @return array
     */
    final public function returnNodes($tree = true) {
        static $tree_nodes = [];
        if ($tree && !empty($tree_nodes[(int)$tree])) {
            return $tree_nodes[$tree];
        }
        $Menu = $this->app->model('menu');
        $model_name = $this->app->request->module(); //当前模块名称
        if ($tree) {
            $list = $Menu->menuField('id,pid,title,url,tip,hide');
            foreach ($list as $key => $value) {
                if (stripos($value['url'], $model_name) !== 0) {
                    $list[$key]['url'] = "{$model_name}/{$value['url']}";
                }
            }
            $nodes = list_to_tree($list, 'id', 'pid', 'operator', 0);
            foreach ($nodes as $key => $value) {
                if (!empty($value['operator'])) {
                    $nodes[$key]['child'] = $value['operator'];
                    unset($nodes[$key]['operator']);
                }
            }
        } else {
            $nodes = $Menu->menuField('title,url,tip,pid');
            foreach ($nodes as $key => $value) {
                if (stripos($value['url'], $model_name) !== 0) {
                    $nodes[$key]['url'] = "{$model_name}/{$value['url']}";
                }
            }
        }
        $tree_nodes[(int)$tree] = $nodes;
        return $nodes;
    }
}