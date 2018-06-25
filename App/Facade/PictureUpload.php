<?php
/**
 * Created by PhpStorm.
 * User: wzj
 * Date: 2018/6/25
 * Time: 19:38
 */

namespace App\Facade;


use think\Facade;

class PictureUpload extends Facade
{
    protected static function getFacadeClass()
    {
        return \App\Internal\PictureUpload::class;
    }
}