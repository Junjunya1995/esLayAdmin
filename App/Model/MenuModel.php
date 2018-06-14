<?php
/**
 * Description of Menu.php.
 * User: static7 <static7@qq.com>
 * Date: 2017-08-04 15:43
 */

namespace App\Model;


use App\Traits\ModelTrait;
use think\Model;

class MenuModel extends Model
{
    use ModelTrait;
    protected $auto = ['title', 'url'];
    protected $insert = ['status' => 1];
    protected $update=[];
    /**
     * 查询父级菜单
     * @param int $pid 父级ID
     * @author staitc7 <static7@qq.com>
     * @return array
     */

    public function father(int $pid = 0): array {
        $object = $this::get(function ($query) use ($pid) {
            $query->where('id', $pid)->field('pid,title');
        });
        return $object ? $object->toArray() : [];
    }

    /**
     * 菜单列表(所有)
     * @author staitc7 <static7@qq.com>
     */

    public function menuListAll(): array {
        $object = $this::all(function ($query) {
            $query->where('status',1)->field('id,title,pid');
        });
        return $object ? $object->toArray() :false;
    }

    /**
     * 根据字段查询菜单
     * @param string $field 查询的字段
     * @author staitc7 <static7@qq.com>
     * @return array|null|object
     */

    public function menuField($field) {
        $object = $this::all(function ($query) use ($field) {
            $query->where('status','<>', -1)->field($field)->order('sort asc');
        });
        return $object ? $object->toArray() : false;
    }

    /**
     * 返回后台节点数据
     * @param boolean $tree 是否返回多维数组结构(生成菜单时用到),为false返回一维数组(生成权限节点时用到)
     *                      注意,返回的主菜单节点数组中有'controller'元素,以供区分子节点和主节点
     * @return array
     * @throws ModelNotFoundException
     * @author 朱亚杰 <xcoolcc@gmail.com>
     */
    final public function returnNodes($tree = true) {
        static $tree_nodes = [];
        if ($tree && !empty($tree_nodes[(int)$tree])) {
            return $tree_nodes[$tree];
        }
        $model_name = 'admin'; //当前模块名称
        if ($tree) {
            $list = $this->menuField('id,pid,title,url,tip,hide');
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
            $nodes = $this->menuField('title,url,tip,pid');
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