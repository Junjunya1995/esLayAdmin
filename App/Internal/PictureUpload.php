<?php
/**
 * Created by PhpStorm.
 * User: wzj
 * Date: 2018/6/25
 * Time: 19:33
 */

namespace App\Internal;

use think\Db;

class PictureUpload
{
    protected $error;
    protected $congif = [
        'picture_path' => '/upload/default/picture/', //默认图片保存路径
        'portrait_path' => '/upload/head_portrait/', //头像保存路劲

        /* 图片上传限制 */
        'picture_upload_restrict' => [
            'size' => 2 * 1024 * 1024, //上传的文件大小限制
            'ext' => 'gif,jpg,jpeg,bmp,png,swf,fla,flv', //允许上传的文件后缀
        ],
    ];


    /**
     * 文件上传
     * @param         $request
     * @param  array  $file_name  要上传的文件名称
     * @param  array  $uploadPath 临时上传路径配置
     * @param  string $driver     上传名称（后期完善）
     * @return array           文件上传成功后的信息
     */
    public function upload($request, $file_name = null, $uploadPath = null, $driver = 'local')
    {
        $uploadPath = empty($picturePath) ? $this->config['picture_path'] : $uploadPath;
        $file       = $request->getUploadedFile($file_name);
        //return $file;
        if (is_array($file)) {
//            foreach ($file as $k => $v) {
//                if (!$v->check(Config::get('config.picture_upload_restrict'))) {
//                    $this->error = $file->getError(); // 上传失败获取错误信息
//                    return false;
//                }
//            }
//            $data = $this->arrayProcess($file, $uploadPath, $driver);
        } else {
            if (!$this->fileCheck($file, $this->congif['picture_upload_restrict'])) {
                $this->error = $file->getError(); // 上传失败获取错误信息
                return false;
            }
            $data = $this->oneProcess($file, $uploadPath, $driver);
        }
        return $data;
    }

    /**
     * 单个文件处理
     * @param array  $file 处理的数据
     * @param  array $uploadPath 上传配置
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */
    private function oneProcess($file, $uploadPath)
    {
        //检测文件 是否存在
        $Picture  = Db::name('Picture');
        $fileInfo = $Picture->where([
            ['md5', '=', $file->hash('md5')],
            ['sha1', '=', $file->hash('sha1')]
            ]);
        if ($fileInfo !== false) {
            return $fileInfo;
        }
        //移动文件
        $info = $file->rule('uniqid')->move(getcwd() . $uploadPath);
        if (!$info) {
            $this->error = $file->getError(); // 上传失败获取错误信息
            return false;
        }
        $data = [
            'md5' => $file->hash('md5'),
            'sha1' => $file->hash('sha1'),
            'path' => $uploadPath . $info->getFilename(),
            'create_time' => $info->getATime(),
            'original_name' => $info->getInfo('name'),
            'file_name' => $info->getFilename()
        ];
        $info = $Picture->renew($data);
        if ($info === false) {
            $this->error = $Picture->getError();
            return false;
        }
        return $info;
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

    protected function fileCheck($file, $rule) {

        if (isset($rule['size']) && $file->getSize > $rule['size']) {
            return false;
        }
        $fileExt = end(explode('.', $file->getClientFileName()));
        if (isset($rule['ext']) && !in_array($fileExt, explode(',', $rule['ext']))) {
            return false;
        }
        return true;
    }
}