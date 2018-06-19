<?php
/**
 * Created by PhpStorm.
 * User: wzj
 * Date: 2018/6/15
 * Time: 23:32
 */

namespace App\Model;


use think\Model;

class Picture extends Model
{
    protected $error;
    protected $autoWriteTimestamp = true; //自动写入创建时间戳字段
    protected $updateTime = false;// 关闭自动写入update_time字段
    protected $insert = ['status' => 1];

    /**
     * 判断图片是否存在
     * @author staitc7 <static7@qq.com>
     * @param string $md5
     * @param string $sha1
     * @return mixed
     */
    public function isExist(string $md5='',string $sha1='')
    {
        if (empty($md5)||empty($sha1)){
            $this->error='参数丢失';
            return false;
        }
        $fileInfo = $this::get(function ($query) use ($md5,$sha1) {
            $query->where('md5',$md5)
                ->where('status',1)
                ->where('sha1',$sha1)
                ->field(['id', 'md5', 'path', 'sha1','file_name','original_name']);
        });
        if($fileInfo){
            return $fileInfo->toArray();
        }
        return false;
    }

    /**
     * 图片添加或者更新
     * @author staitc7 <static7@qq.com>
     * @param array|null $data
     * @return mixed
     */
    public function renew(?array $data=[])
    {
        if (empty($data)){
            $this->error='参数错误!';
            return false;
        }
        $object=  $this::create($data);
        if($object){
            return $object->visible(['id', 'md5', 'path', 'sha1','original_name'])->toArray();
        }
        return false;
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

    /*=============获取器==============*/
    /**
     * 完整地址
     * @author staitc7 <static7@qq.com>
     * @param $value
     * @return mixed
     */
    public function getPathAttr($value)
    {
        return empty($value) ? null: EASYSWOOLE_ROOT . $value;
    }
}