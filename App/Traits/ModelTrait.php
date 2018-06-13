<?php
/**
 * Created by PhpStorm.
 * User: wzj
 * Date: 2018/6/8
 * Time: 14:30
 */

namespace App\Traits;

use EasySwoole\Config;
use think\Validate;

trait ModelTrait
{
    //定义错误信息
    protected $error;

    /**
     * @param array $map 条件
     * @param string $field 字段
     * @param string $order 排序
     * @param int $limit 限制条数
     * @return mixed
     */
    public function lists(array $map = [],  $field = '*', string $order = '', int $limit = 0)
    {
        return $object = $this::all(function ($query) use ($map, $field, $order, $limit) {
            $query->where($map ?: null)
                ->field($field ?: '*')
                ->order($order ?: $this->pk.' ASC')
                ->limit($limit ?: 10);
        });
        //return $object ? $object->toArray() : false;
    }

    /**
     * 数据分页
     * @author staitc7 <static7@qq.com>
     * @param array  $map 条件
     * @param string $field 字段
     * @param string $order 排序
     * @param int    $limit 限制条数
     * @param int    $page 当前页面
     * @param array $query 额外参数
     * @return mixed
     */
    public function listsPage(array $map = [],  $field = '*', string $order = '', int $page = 1,int $limit = 0, array $query = [])
    {
        $object = $this::where($map ?: null)
            ->field($field ?: '*')
            ->order($order ?: $this->pk.' ASC')
            ->paginate([
                'page' => $page,
                'query' => $query,
                'list_rows' => $limit ?: 10
            ]);
        return $object ? array_merge($object->toArray(),['page'=>$object->render()]) : false;
    }

    /**
     * 数据json分页
     * @author staitc7 <static7@qq.com>
     * @param array  $map 条件
     * @param string $field 字段
     * @param string $order 排序
     * @param int    $limit 限制条数
     * @param int    $page 当前页面
     * @param array $query 额外参数
     * @return mixed
     */
    public function listsJson(array $map = [], $field = '*', string $order = '', int $page = 1,int $limit = 0, array $query = [])
    {
        $object = $this::where($map ?: null)
            ->field($field ?: '*')
            ->order($order ?: $this->pk.' ASC')
            ->paginate([
                'page' => $page,
                'query' => $query,
                'list_rows' => $limit ?: 10
            ]);
        return $object ? $object->toArray() : false;
    }

    /**
     * 单条数据详情
     * @author staitc7 <static7@qq.com>
     * @param int         $id 数据ID
     * @param array|null  $tmp_map 附加条件
     * @param null|string $field
     * @return mixed
     */
    public function edit(int $id=0, $field = '*',array $tmp_map =[])
    {
        if ((int)$id<1){
            $this->error='参数错误';
            return false;
        }
        $map = [[$this->pk, '=', (int)$id]];
        empty($tmp_map) || $map[]=$tmp_map;
        $object=$this::get(function ($query)use($map,$field){
            $query->where($map)->field($field ?: '*');
        });
        return $object ?: false;
    }

    /**
     * 单条数据详情  edit 的别名 我觉得用这个名字比较好
     * @param int $id
     * @param string $field
     * @param array $tmp_map
     * @return mixed
     */
    public function getInfo(int $id=0, $field = '*',array $tmp_map =[]){
        return $this->edit($id, $field, $tmp_map);
    }

    /**
     * 用户更新或者添加
     * @author staitc7 <static7@qq.com>
     * @param array  $data 数据
     * @param bool   $rule 验证场景
     * @param string $pkId 主键
     * @param string $validate 验证器类名
     * @return bool
     */

    public function renew(array $data=null,bool $rule=false,string $pkId='',$validate='') {

        if (empty($data) || !is_array($data)) {
            return false;
        }

        $validate = $this->getValidate($validate);
        $pk= $pkId ?: $this->pk;
        if($rule || isset($data[$pk])) {
            $validate->scene('edit');
        }
        if (!$validate->check($data)) {
            $this->error = $validate->getError();// 验证失败 输出错误信息
            return false;
        }

        if ((isset($data[ $pk ]) && (int)$data[ $pk ]>0)){
            //操作类型 1 更新 2添加
            $object = $this::update($data);
            $type   =  1;
        }else{
            $object = $this::create($data);
            $type   =  2;
        }

        return $object ? $object->toArray() : false;
    }


    /**
     * 修改状态
     * @param int|array $map 数据的ID或者ID组
     * @param array $data 要修改的数据
     * @author staitc7 <static7@qq.com>
     * @return bool|int|string
     */

    public function setStatus($map = null, $data = null) {
        if (empty($map) || empty($data)) {
            return false;
        }
        $object=$this::where($map)->update($data);

        return $object;
    }

    /**
     * 返回模型的错误信息
     * @access public
     * @return string|array
     */
    public function getError()
    {
        return $this->error;
    }


    private function getValidate ($validate = '') :?Validate {
        if(empty($validate_name)){//获取验证器
            $class_name=get_class();
            $start=strrpos($class_name,'\\')+1;
            $validate=substr($class_name,$start, 4);
            $validate = 'App\Validates\\' . $validate . 'Validate';
        }

        return new $validate();
    }
}