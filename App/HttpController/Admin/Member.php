<?php
/**
 * Created by PhpStorm.
 * User: wzj
 * Date: 2018/6/11
 * Time: 0:07
 */

namespace App\HttpController\Admin;


class Member extends Admin
{
    public function index()
    {
       // $this->dump($this->getMenus());
        $this->fetch();
    }
}