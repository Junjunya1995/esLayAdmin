<?php
/**
 * Created by PhpStorm.
 * User: wzj
 * Date: 2018/6/8
 * Time: 16:56
 */
// 应用公共文件

/**
 * 随机数生成
 * @param int $leng
 * @return int
 */
function get_random($leng=6){
    //range 是将0到9列成一个数组
    $numbers = range (0,9);
    shuffle ($numbers);//shuffle 将数组顺序随即打乱
    $random = "";
    for ($i=0;$i<$leng;$i++){
        //取值起始位置随机
        $rand = mt_rand(0,9);
        $random .=$numbers[$rand];
    }
    return $random;
}

/**
 * 时间戳格式化
 * @param int $time
 * @param string $format 时间格式
 * @return string 完整的时间显示
 * @author huajie <banhuajie@163.com>
 */
function time_format($time = null, $format = 'Y-m-d H:i:s') {
    return (is_numeric($time) && (int)$time > 0) ? date($format, $time) : '';
}

/**
 * 从数组中取出索引项
 * @param type $arg 参数
 * @param array|type $list 数组
 * @return string
 */
function change_status($arg, $list = ['-1' => '删除', '0' => '禁用', '1' => '正常']) {
    if (array_key_exists($arg, $list)) {
        $value = $list[$arg];
    }
    return $value ?? '未知';
}

/**
 * 检测头像
 * @param int $user_id 用户ID
 * @author staitc7 <static7@qq.com>
 * @return mixed|string
 */
function portrait($user_id = null) {
    $id = empty($user_id) ? Session::get('user_auth.uid') : $user_id;
    $info = Cookie::get("user_{$id}", "portrait_");
    if ($info) {
        return $info;
    }
    $portrait_id = Db::name('Member')->where('uid', '=',  $id)->value('portrait');

    $path = (int)$portrait_id > 0 ? get_cover($portrait_id) :Request::rootUrl().'/'.Request::module().'/images/null.gif';
    Cookie::set("user_{$id}", $path, ['prefix' => 'portrait_', 'expire' => 86400]);
    return $path;

}

/**
 * 获取文档封面图片
 * @param int $cover_id
 * @param string $fields
 * @return string
 * @author huajie <banhuajie@163.com>
 */
function get_cover($cover_id = 0, $fields = '') {
    $default_path=Request::rootUrl().'/'.Request::module().'/imgs/null.gif';
    if ((int)$cover_id <1 ){//返回默认图片
        return $default_path;
    }
    $field=$fields ?:['path','url'];
    $info= Db::name('Picture')->where([['status', '=',  1],['id', '=', $cover_id]])->field($field)->find();

    if (empty($info)){
        return $default_path;
    }else{
        return $fields ? $info[$fields] : $info['url'] ?: Request::rootUrl().$info['path'];
    }
}

/**
 * 格式化字节大小
 * @param  number $size 字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function format_bytes($size, $delimiter = '') {
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    for ($i = 0; $size >= 1024 && $i < 5; $i++) {
        $size /= 1024;
    }
    return $size . $delimiter . $units[$i];
}

/**
 * 字符串截取，支持中文和其他编码
 * @param string $str 需要转换的字符串
 * @param int|string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param bool|string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
    if (function_exists("mb_substr")) {
        $slice = mb_substr($str, $start, $length, $charset);
    } elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice . '...' : $slice;
}

/**
 *  不区分大小写的in_array实现
 * @author staitc7 <static7@qq.com>
 * @param $value
 * @param $array
 * @return bool
 */
function in_array_case($value, $array = [])
{
    return in_array(strtolower($value), array_map('strtolower', $array ?? []));
}

/**
 * 系统非常规MD5加密方法
 * @param  string $str 要加密的字符串
 * @param string $key 默认密钥
 * @return string
 */
function ucenter_md5($str, $key = '')
{
    $key = empty($key) ? 'F:x2d"<f)#s}DR$*7A|HU/4hgXLcGwoMKO(50p_b' : $key;
    return (string)$str === '' ? '' : md5(sha1($str) . $key);
}

/**
 * 数据签名认证
 * @param  array $data 被认证的数据
 * @return string       签名
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function data_auth_sign(array $data) {
    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    return sha1($code);//生成签名
}