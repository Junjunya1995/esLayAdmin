<?php
/**
 * Description of Deploy.php.
 * User: static7 <static7@qq.com>
 * Date: 2017-08-03 14:31
 */

namespace App\Model\Admin;


use App\Traits\ModelTrait;
use EasySwoole\Config;
use think\Model;

class Deploy extends Model
{
    use ModelTrait;
    protected $autoWriteTimestamp = true; //自动写入创建和更新的时间戳字段
    protected $auto = ['title', 'name'];
    protected $insert = ['status' => 1];
    protected $update = [];

    /**
     * 批量保存配置
     * @param array $data 配置数据
     * @author staitc7 <static7@qq.com>
     * @return array
     */

    public function batchSave(array $data = []) {
        foreach ($data as $name => $value) {
           $status= $this::where('name', '=', $name)->setField('value', $value);
           if ($status===false){
               $this->error='系统错误，请稍候再试';
               return false;
           }
        }
        return true;
    }

    /*====================获取器====================*/

    /**
     * 获取配置的分组
     * @param  $value 配置分组
     * @author staitc7 <static7@qq.com>
     * @return string
     */
    function getGroupAttr($value)
    {
       // $list = Config::get('admin_config.config_group_list');
        //return (int)$value ? $list[ $value ] : '不予显示';
    }

    /**
     * 配置区域
     * @author staitc7 <static7@qq.com>
     * @param $value
     * @return mixed
     */
    public function getAreaAttr($value)
    {
        return is_numeric($value) ? change_status($value,['前后台','前台','后台']):null;
    }

    /**
     * 配置类型
     * @author staitc7 <static7@qq.com>
     * @param $value
     * @return mixed
     */
    public function getTypeAttr($value)
    {
        $list = [
            1 => '字符',
            2 => '文本',
            3 => '数组',
            4 => '枚举',
            5 => '多维枚举',
            6 => '数字',
        ];
        return (int)$value ? $list[ $value ] : '';
    }

}