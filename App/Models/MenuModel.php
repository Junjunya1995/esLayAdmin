<?php
/**
 * Description of Menu.php.
 * User: static7 <static7@qq.com>
 * Date: 2017-08-04 15:43
 */

namespace App\Models;


use App\Traits\ModelTrait;
use think\Model;

class MenuModel extends Model
{
    use ModelTrait;
    protected $auto = ['title', 'url'];
    protected $insert = ['status' => 1];
    protected $update=[];
    protected $name = 'menu';
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


}