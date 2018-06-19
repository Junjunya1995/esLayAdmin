<?php
/**
 * Description of AuthRule.php.
 * User: static7 <static7@qq.com>
 * Date: 2017-08-09 14:33
 */

namespace App\Model\Admin;



use App\Traits\ModelTrait;
use think\Model;

class AuthRule extends Model
{
    use ModelTrait;
    /**
     * 获取全部列表,以进行更新
     * @author Static7 <static7@qq.com>
     * @return mixed
     */
    public function ruleList(): array
    {
        $object = $this::all(function ($query) {
            $query->where('module' , 'admin')->where('type','in','1,2')->order('name asc');
        });
        return $object ? $object->toArray() : false;
    }

    /**
     * 权限规则数组更新
     * @param array $data 更新的数组
     * @param array $ids 数组id
     * @author staitc7 <static7@qq.com>
     * @return $this
     */

    public function arrayUpdate(array $data = [], array $ids = []) {
        if (empty($ids)) {
           $info= $this::update($data);
        } else {
           $info= $this::where('id' ,'in', $ids)->update($data);
        }
        return $info;
    }

    /**
     * 条件查询权限
     * @param array $map 查询条件
     * @param boole|bool|string $field 查询的字段
     * @return array|null|object
     * @author staitc7 <static7@qq.com>
     */

    public function mapList(array $map = [], $field = true) {
        $object = $this::all(function($query)use($map, $field) {
            $query->where($map)->field($field);
        });
        return $object ? $object->toArray() : false;
    }

    /**
     * 添加菜单
     * @param array $data 添加的数据
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */

    public function menuAdd($data) {
       $this::create($data);
    }
}