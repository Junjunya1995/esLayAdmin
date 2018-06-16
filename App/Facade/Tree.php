<?php
/**
 * Created by PhpStorm.
 * User: wzj
 * Date: 2018/6/16
 * Time: 10:39
 */

namespace App\Facade;


use think\Facade;

class Tree extends Facade
{
    protected static function getFacadeClass()
    {
        return \App\Internal\Tree::class;
    }
}