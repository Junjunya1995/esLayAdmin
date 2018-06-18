<?php
/**
 * 新增部分自定义验证规则
 * User: wzj
 * Date: 2018/6/16
 * Time: 14:38
 */

namespace Lib;

use think\Db;
use  think\Validate as tpValidate;

class Validate extends tpValidate
{

    /**
     * 验证是否唯一
     * @access public
     * @param  mixed  $value 字段值
     * @param  mixed  $rule  验证规则 格式：数据表,字段名,排除ID,主键名
     * @param  array  $data  数据
     * @param  string $field 验证字段名
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function unique($value, $rule, $data, $field)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }

        if (false !== strpos($rule[0], '\\')) {
            // 指定模型类
            $db = new $rule[0];
        } else {
                $db = Db::name($rule[0]);
        }

        $key = isset($rule[1]) ? $rule[1] : $field;

        if (strpos($key, '^')) {
            // 支持多个字段验证
            $fields = explode('^', $key);
            foreach ($fields as $key) {
                $map[] = [$key, '=', $data[$key]];
            }
        } else {
            $map[] = [$key, '=', $data[$field]];
        }

        $pk = !empty($rule[3]) ? $rule[3] : $db->getPk();

        if (is_string($pk)) {
            if (isset($rule[2])) {
                $map[] = [$pk, '<>', $rule[2]];
            } elseif (isset($data[$pk])) {
                $map[] = [$pk, '<>', $data[$pk]];
            }
        }

        if ($db->where($map)->field($pk)->find()) {
            return false;
        }

        return true;
    }
}