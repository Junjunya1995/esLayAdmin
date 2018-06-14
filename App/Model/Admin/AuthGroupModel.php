<?php
/**
 * Description of AuthGroup.php.
 * User: static7 <static7@qq.com>
 * Date: 2017-08-03 12:01
 */

namespace App\Model\Admin;

use App\Traits\ModelTrait;
use think\Model;

class AuthGroupModel extends Model
{
    use ModelTrait;
    protected $auto = ['description', 'title', 'type'];
    protected $insert = [
        'status' => 1,
        'module'
    ];
    protected $update = [];

    /**
     * 用户组详情
     * @param int $id 用户组详情
     * @param bool $field
     * @return array
     * @author staitc7 <static7@qq.com>
     */

    public function editGroup(int $id, $field = true): array {
        $object = $this::get(function($query)use($id, $field) {
            $query->where('id', $id)->where('status',1)->field($field);
        });
        return $object ? $object->toArray() : [];
    }

    /**
     * 检查用户组是否存在
     * @author staitc7 <static7@qq.com>
     * @param int $group_id 组id
     * @return bool|mixed
     */

    public function checkGroupId(int $group_id=0) {
        $object = $this::get($group_id);
        return $object ? $object->id : false;
    }

    /**
     * 条件查询用户组
     * @param array $map 查询条件
     * @param boole|bool|string $field 查询的字段
     * @return array|null|object
     * @author staitc7 <static7@qq.com>
     */

    public function mapList($map = [], $field = true)
    {
        $object = $this::all(function ($query) use ($map, $field) {
            $query->where($map)->field($field);
        });
        return $object ? $object->toArray() : false;
    }

    /*==============获取器==============*/

    /**
     *
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */
    public function getDescriptionAttr($value)
    {
        return $value ? mb_strimwidth($value,0,40,"...","utf-8"):null;
    }

    /*==============数据自动完成==============*/

    /**
     * 自动获取模块
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */
    protected function setModuleAttr($value)
    {
        return $value ?: 'admin';
    }


    /**
     * 过滤非法字符description
     * @author staitc7 <static7@qq.com>
     * @param $value
     * @return string
     */

    protected function setDescriptionAttr($value) {
        return htmlspecialchars($value);
    }

    /**
     * 过滤非法字符description
     * @author staitc7 <static7@qq.com>
     * @param $value
     * @return string
     */

    protected function setTitleAttr($value) {
        return htmlspecialchars($value);
    }

    /**
     * 组类型 type
     * @author staitc7 <static7@qq.com>
     */

    protected function setTypeAttr() {
        return Config::get('config.auth_config.type_admin');
    }

}