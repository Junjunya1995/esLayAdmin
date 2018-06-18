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

    /**
     * 用户
     * @return mixed
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userJson()
    {
        $data = $this->request()->getParsedBody();
        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 10;
        if (!$this->requestex()->isAjax()) {
            return $this->error('系统错误!,请重新刷新页面');
        }
        $data = $this->model('MemberModel')->listsJson([['status','<>',-1]], '', '', (int)$page ?: 1,$limit);
        $this->layuiJson($data);
    }

    /**
     * 添加用户
     */
    public function add() {
        $this->fetch('/Admin/Member/add', ['metaTitle' => '添加会员']);
    }

    /**
     * 添加用户
     * @return mixed
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function renew()
    {
        $Member =$this->model('MemberModel');
        $data = $this->requestex()->param();
        $data['reg_ip'] = $this->requestex()->getIp();
        $info=$Member->userAdd($data);
        if ($info===false) {
            return $this->error($Member->getError());
        }
        $this->success('新增成功','/admin/member/index');

    }


    /**
     * 编辑用户
     * @author staitc7 <static7@qq.com>
     * @param int $uid
     * @return mixed
     */
    public function edit($uid=0)
    {
        if ((int)$uid>0){
            $info=$this->app->model('Member')->edit($uid);
        }
        //print_r($info);exit;
        return $this->setView(['info'=>$info ?? null],'detail');
    }

    /**
     * 数据状态修改
     * @param int $value 状态
     * @param null $ids
     * @internal param ids $int 数据条件
     * @author staitc7 <static7@qq.com>
     */
    public function setStatus($value = null, $ids = null) {
        empty($ids) && $this->error('请选择要操作的数据');
        is_numeric((int)$value) || $this->error('参数错误');
        $info = $this->app->model('Member')->setStatus([['uid','in', (int)$ids]], ['status' => $value]);
        return $info !== false ?
            $this->success($value == -1 ? '删除成功' : '更新成功') :
            $this->error($value == -1 ? '删除失败' : '更新失败');
    }

    /**
     * 批量数据更新
     * @param int $value 状态
     * @author staitc7 <static7@qq.com>
     */
    public function batchUpdate($value = null) {
        $ids = array_unique($this->app->request->post()['ids']);
        empty($ids) && $this->error('请选择要操作的数据');
        if (in_array((string)UserInfo::isAdmin(), $ids, true)) {
            $this->error('用户中包含超级管理员，不能执行该操作');
        }
        !is_numeric((int)$value) && $this->error('参数错误');
        $info = $this->app->model('Member')->setStatus([['uid','in', $ids]], ['status' => $value]);
        return $info !== false ?
            $this->success($value == -1 ? '删除成功' : '更新成功') :
            $this->error($value == -1 ? '删除失败' : '更新失败');
    }
}