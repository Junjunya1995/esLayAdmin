<?php
/**
 * Created by PhpStorm.
 * User: wzj
 * Date: 2018/6/13
 * Time: 20:34
 */

namespace App\HttpController\Admin;


use think\Db;

class AuthManager extends Admin
{
    /**
     * 权限管理
     * @return mixed
     * @throws \Exception
     */
    public function index()
    {
        $this->fetch('', ['metaTitle' => '权限管理']);
    }

    /**
     * 权限管理
     * @return mixed
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function authJson()
    {
        $data = $this->request()->getParsedBody();
        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 10;
        $map = [
            ['module', '=', 'admin'],
            ['status', '<>', -1]
        ];
        $data = $this->model('AuthGroup')->listsJson($map, null, 'id asc', (int)$page ?: 1,$limit);
        return $this->layuiJson($data);
    }

    /**
     * 访问授权页面
     * @return mixed
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function access() {
        $group_id = $this->request()->getQueryParam('group_id') ?? 0;
        (int)$group_id || $this->error('用户组ID错误');
        $this->updateRules();
        $auth_group = $this->authGroup();
        $node_list = $this->model('Menu')->returnNodes();
        $map = ['status','=',1];
        $ruleArray= ['type','=',2];
        $AuthRule = $this->model('AuthRule');
        $main_rules = $AuthRule->mapList([$map,$ruleArray], 'name,id');
        $ruleArray=['type','=', 1];
        $child_rules = $AuthRule->mapList([$map,$ruleArray], 'name,id');

        $this->fetch('',[
            'main_rules' => array_column($main_rules,'id','name'),
            'auth_rules' => array_column($child_rules,'id','name'),
            'node_list' => $node_list,
            'auth_group' => $auth_group,
            'this_group' => $auth_group[$group_id],
            'group_id' => $group_id,
            'metaTitle'=>'访问授权'
        ]);
    }

    /**
     * 用户组授权用户
     * @return mixed
     */
    public function user() {
        $group_id = $this->requestex()->get('group_id');
        (int)$group_id || $this->error('用户组ID错误');
        $auth_group = $this->authGroup();
        $this->fetch('', [
            'auth_group' => $auth_group,
            'group_id' => $group_id,
            'metaTitle'=> '用户授权'
        ]);
    }

    /**
     * 用户组授权用户列表
     * @param int $group_id 组ID
     * @param int $page 页码
     * @param int $limit 每条页数
     * @return mixed
     */
    public function userJson()
    {
        $data = $this->requestex()->param();
        $group_id = $data['group_id'] ?? 0;
        $limit = $data['limit'] ?? 10;
        $page = $data['page'] ?? 1;

        $member = 'member';
        $auth_group_access = 'auth_group_access';
        $viewData = Db::view($member, 'uid,nickname,last_login_time,last_login_ip,status')
            ->view($auth_group_access, 'group_id', "{$auth_group_access}.uid={$member}.uid")
            ->where('group_id','=',$group_id)
            ->where('status','>=', 0)
            ->order('uid asc')
            ->paginate(['page' => $page, 'list_rows'=>$limit]);
        if ($viewData){
            $data=$viewData->toArray();
            foreach ($data['data'] as $k=>&$v){
                $v['last_login_time']=time_format($v['last_login_time']);
                $v['last_login_ip']=long2ip($v['last_login_ip']);
            }
        }
        return $this->layuiJson($data??null);
    }

    /**
     * 将用户添加到用户组,入参uid,group_id
     * @param string $uid 用户ID
     * @param int $group_id 用户组ID
     * @author staitc7 <static7@qq.com>
     */
    public function addToGroup($uid = null, $group_id = 0) {
        (int)$group_id || $this->error('参数错误');
        empty($uid) && $this->error('用户组ID不能为空');
        $user_id = array_filter(explode(',', $uid));
        $Member=$this->app->model('Member');
        foreach ($user_id as $k=>$v) {
            UserInfo::isAdmin((int)$v) && $this->error("编号 {$v} 该用户为超级管理员,不能添加!");
            empty($Member->userId((int)$v)) && $this->error("编号 {$v} 用户不存在");
        }
        //检查用户组
        $auth_group = $this->app->model('AuthGroup')->checkGroupId($group_id);
        empty($auth_group) && $this->error('该用户组不存在');
        //用户添加到用户组
        $AuthGroupAccess = $this->app->model('AuthGroupAccess');
        $info = $AuthGroupAccess->addToGroup($user_id, $group_id);
        return $info===true ? $this->success('操作成功') : $this->error($info);
    }

    /**
     * 解除用户授权访问
     * @param int $uid 用户id
     * @param int $group_id 组id
     * @author staitc7 <static7@qq.com>
     */
    public function removeFromGroup($uid = 0, $group_id = 0) {
        (int)$uid || $this->error('用户ID错误');
        (int)$group_id || $this->error('参数错误');
        if ((int)$uid === UserInfo::userId()) {
            return $this->error('不允许解除自身授权');
        }
        $group = $this->app->model('AuthGroup')->checkGroupId($group_id);
        if(empty($group)){
            return $this->error('用户组不存在');
        }
        $info = $this->app->model('AuthGroupAccess')->removeFromGroup($uid, $group_id);
        return $info !== false ?
            $this->success('解除授权成功') :
            $this->error('解除授权失败');
    }

    /**
     * 获取用户权限组
     * @author staitc7 <static7@qq.com>
     */
    protected function authGroup() {
        $map = [
            ['status','>=', '0'],
            ['module' ,'=', 'admin'],
            ['type' ,'=',1]
        ];
        $auth_group_tmp = $this->model('AuthGroup')->mapList($map, 'id,title,rules');
        $auth_group = null;
        if ($auth_group_tmp) {
            foreach ($auth_group_tmp as $k => $v) {
                $auth_group[$v['id']] = $v;
            }
            unset($auth_group_tmp);
        }
        return $auth_group;
    }

    /**
     * 用户组详情
     * @param int $id 用户组ID
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */
    public function editGroup($id = 0) {
        if ((int)$id > 0) {
            $info = $this->app->model('AuthGroup')->editGroup((int)$id);
        }
        return $this->setView(['info'=>$info ?? null],'edit_group',false);
    }

    /**
     * 用户组添加或者更新
     * @author staitc7 <static7@qq.com>
     */
    public function writeGroup() {
        $AuthGroup = $this->app->model('AuthGroup');
        $info= $AuthGroup->renew();
        if ($info===false) {
            $this->error($AuthGroup->getError());
        }
        return $this->success('操作成功', $this->app->url->build('AuthManager/index'));
    }

    /**
     * 批量数据更新
     * @param int $value 状态
     * @author staitc7 <static7@qq.com>
     */
    public function batchUpdate($value = null) {
        $ids = $this->app->request->post();
        empty($ids['ids']) && $this->error('请选择要操作的数据');
        is_numeric((int)$value) || $this->error('参数错误');
        $info = $this->app->model('AuthGroup')->setStatus([['id','in', $ids['ids']]], ['status' => $value]);
        return $info !== false ?
            $this->success($value == -1 ? '删除成功' : '更新成功') :
            $this->error($value == -1 ? '删除失败' : '更新失败');
    }

    /**
     * 单条数据状态修改
     * @param int $value 状态
     * @param null $ids
     * @internal param ids $int 数据条件
     * @author staitc7 <static7@qq.com>
     */
    public function setStatus($value = null, $ids = null) {
        empty($ids) && $this->error('请选择要操作的数据');
        is_numeric((int)$value) || $this->error('参数错误');
        $info = $this->app->model('AuthGroup')->setStatus([['id' ,'in', $ids]], ['status' => $value]);
        return $info !== false ?
            $this->success($value == -1 ? '删除成功' : '更新成功') :
            $this->error($value == -1 ? '删除失败' : '更新失败');
    }


    /**
     * 用户授权
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function group() {
        $id = $this->requestex()->get('id') ?? 0;
        (int)$id || $this->error('参数错误');
        $AuthGroup = $this->model('AuthGroup');
        $map = [
            ['status', '=', 1],
            ['type', '=', 1],
            ['module', '=', 'admin']
        ];
        $auth_groups = $AuthGroup->mapList($map, 'id,title');
        $auth_group_access = 'auth_group_access';
        $auth_group = 'auth_group';
        $user_group = Db::view($auth_group_access, 'uid,group_id')
            ->view($auth_group, 'id', "{$auth_group_access}.group_id={$auth_group}.id")
            ->where("uid = :uid and status = :status",['uid' => $id, 'status' => 1])
            ->select();
        $user_groups = $user_group ? array_column($user_group, 'group_id') : null;
        $this->fetch('', [
            'user_id' => $id,
            'auth_groups' => $auth_groups,
            'user_groups' => $user_groups ? implode(',', $user_groups) : null
        ]);
    }

    /**
     * 用户添加到用户组
     * @param array $group_id 用户组ID
     * @param int   $uid 用户ID
     * @author staitc7 <static7@qq.com>
     */
    public function userToGroup($group_id = [], $uid = 0)
    {
        $uid = $this->requestex()->param('uid') ?? 0;
        $group_id = $this->requestex()->param('group_id') ?? [];
        (int)$uid || $this->error('用户ID错误');
        $this->isAdmin() && $this->error("该用户为超级管理员");
        $group_ids = [];
        if (!empty($group_id)) {
            $group_ids = array_filter($group_id);
            $AuthGroup = $this->model('AuthGroup');
            foreach ($group_ids as $v) {
                if (empty($AuthGroup->checkGroupId((int)$v))) {
                    return $this->error("编号 {$v} 用户组不存在");
                };
            }
        }
        $AuthGroupAccess = $this->model('AuthGroupAccess');
        $info            = $AuthGroupAccess->userToGroup($uid, $group_ids);
        return $info ? $this->success('操作成功') : $this->error($AuthGroupAccess->getError());
    }


    /**
     * 后台节点配置的url作为规则存入auth_rule
     * 执行新节点的插入,已有节点的更新,无效规则的删除三项任务
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    private function updateRules()
    {
        $nodes = $this->model('Menu')->returnNodes(false);//需要新增的节点必然位于$nodes
        $AuthRule = $this->model('AuthRule');
        $rules = $AuthRule->ruleList();//需要更新和删除的节点必然位于$rules
        if (empty($rules)) {
            //return $this->error('没有权限规则');
        }
        //构建insert数据
        $data = []; //保存需要插入和更新的新节点
        foreach ($nodes as $value) {
            $temp['name'] = $value['url'];
            $temp['title'] = $value['title'];
            $temp['module'] = 'admin';
            $temp['type'] = $value['pid'] > 0 ? 1 :  2;
            $temp['status'] = 1;
            $data[strtolower($temp['name'] . $temp['module'] . $temp['type'])] = $temp; //去除重复项
        }
        $update = []; //保存需要更新的节点
        $ids = []; //保存需要删除的节点的id
        foreach ($rules as $index => $rule) {
            $key = strtolower($rule['name'] . $rule['module'] . $rule['type']);
            if (isset($data[$key])) {//如果数据库中的规则与配置的节点匹配,说明是需要更新的节点
                $data[$key]['id'] = $rule['id']; //为需要更新的节点补充id值
                $update[] = $data[$key];
                unset($data[$key]);
                unset($rules[$index]);
                unset($rule['condition']);
                $diff[$rule['id']] = $rule;
            } elseif ($rule['status'] == 1) {
                $ids[] = $rule['id'];
            }
        }
        if (count($update)) {
            foreach ($update as $k => $row) {
                if ($row != $diff[$row['id']]) {
                    $AuthRule->arrayUpdate($row);
                }
            }
        }
        count($ids) > 0 && $AuthRule->arrayUpdate(['status' => -1], $ids); //删除规则是否需要从每个用户组的访问授权表中移除该规则?
        if (count($data)) { //新添菜单
            foreach ($data as $k => $row) {
                $AuthRule->menuAdd($row);
            }
        }
        if ($AuthRule->getError()) {
            //$this->app->log->record("[ 信息 ]：" . $AuthRule->getError());
            return false;
        }
        return true;
    }

    /**
     * 管理员用户组数据写入/更新
     * @author staitc7 <static7@qq.com>
     */
    public function rulesArrayUpdate()
    {
        $rules = $this->requestex()->post();
        (int)$rules['id'] || $this->error('参数错误');
        if (isset($rules['rules']) && !empty($rules['rules'])) {
            sort($rules['rules']);
        } else {
            $rules['rules'] = [];
        }
        $data = [
            'id' => $rules['id'], 'module' => 'admin',
            'rules' => implode(',', array_unique($rules['rules'])),
            'type' => 1
        ];
        $info = Db::name('AuthGroup')->update($data);
        return $info !== false ?
            $this->success('操作成功', '/admin/authManager/index') :
            $this->error($info);
    }
}