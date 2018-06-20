<?php
/**
 * Created by PhpStorm.
 * User: wzj
 * Date: 2018/6/16
 * Time: 11:23
 */

namespace Lib;


/**
 * 权限认证类
 * 功能特性：
 * 1，是对规则进行认证，不是对节点进行认证。用户可以把节点当作规则名称实现对节点进行认证。
 *      $auth=new Auth();  $auth->check('规则名称','用户id')
 * 2，可以同时对多条规则进行认证，并设置多条规则的关系（or或者and）
 *      $auth=new Auth();  $auth->check('规则1,规则2','用户id','and')
 *      第三个参数为and时表示，用户需要同时具有规则1和规则2的权限。 当第三个参数为or时，表示用户值需要具备其中一个条件即可。默认为or
 * 3，一个用户可以属于多个用户组(think_auth_group_access表 定义了用户所属用户组)。我们需要设置每个用户组拥有哪些规则(think_auth_group 定义了用户组权限)
 *
 * 4，支持规则表达式。
 *      在think_auth_rule 表中定义一条规则时，如果type为1， condition字段就可以定义规则表达式。 如定义{score}>5  and {score}<100  表示用户的分数在5-100之间时这条规则才会通过。
 */
//数据库
/*
  -- ----------------------------
  -- think_auth_rule，规则表，
  -- id:主键，name：规则唯一标识, title：规则中文名称 status 状态：为1正常，为0禁用，condition：规则表达式，为空表示存在就验证，不为空表示按照条件验证
  -- ----------------------------
  DROP TABLE IF EXISTS `think_auth_rule`;
  CREATE TABLE `think_auth_rule` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(80) NOT NULL DEFAULT '',
  `title` char(20) NOT NULL DEFAULT '',
  `type` tinyint(1) NOT NULL DEFAULT '1',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `condition` char(100) NOT NULL DEFAULT '',  # 规则附件条件,满足附加条件的规则,才认为是有效的规则
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
  -- ----------------------------
  -- think_auth_group 用户组表，
  -- id：主键， title:用户组中文名称， rules：用户组拥有的规则id， 多个规则","隔开，status 状态：为1正常，为0禁用
  -- ----------------------------
  DROP TABLE IF EXISTS `think_auth_group`;
  CREATE TABLE `think_auth_group` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` char(100) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `rules` char(80) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
  -- ----------------------------
  -- think_auth_group_access 用户组明细表
  -- uid:用户id，group_id：用户组id
  -- ----------------------------
  DROP TABLE IF EXISTS `think_auth_group_access`;
  CREATE TABLE `think_auth_group_access` (
  `uid` mediumint(8) unsigned NOT NULL,
  `group_id` mediumint(8) unsigned NOT NULL,
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
  KEY `uid` (`uid`),
  KEY `group_id` (`group_id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 */

use EasySwoole\Core\Http\Session\Session;
use http\Env\Request;
use think\Db;

class Auth {
    private $request;
    private $session;

    //默认配置
    protected $config = [
        'auth_on' => true, // 认证开关
        'auth_type' => 1, // 认证方式，1为实时认证；2为登录认证。
        'auth_group' => 'auth_group', // 用户组数据表名
        'auth_group_access' => 'auth_group_access', // 用户-用户组关系表
        'auth_rule' => 'auth_rule', // 权限规则表
        'auth_user' => 'member', // 用户信息表
        'auth_user_field'=>['score'],
        'type_admin' => 1, //管理员用户组类型标识
        'ucenter_member' => 'ucenter_member',
        'auth_extend' => 'auth_extend', //动态权限扩展信息表
        'auth_extend_category_type' => 1, //分类权限标识
        'auth_extend_model_type' => 2//模型权限标识
    ];

    public function __construct(HttpRequest $request ,Session $session) {
        $this->session = $session;
        $this->request = $request;
    }


    /**
     * 检查权限
     * @param  string|array $name     需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param   int         $uid      认证用户的id
     * @param   int         $type
     * @param string        $mode     执行check的模式
     * @param string        $relation 如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     * @return boolean           通过验证返回true;失败返回false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function check($name, $uid, $type = 1, $mode = 'url', $relation = 'or') {

        if (!$this->config['auth_on']) {
            return true;
        }
        $authList = $this->getAuthList($uid, $type); //获取用户需要验证的所有有效规则列表
        if ($authList===false){
            return false;
        }
        if (is_string($name)) {
            $name = strtolower($name);
            $name = strpos($name, ',') !== false ? explode(',', $name) : [$name];
        }
        if ($mode == 'url') {
            $request = unserialize(strtolower(serialize($this->request->param())));
        }

        $list = []; //保存验证通过的规则名
        foreach ($authList as $auth) {
            $query = preg_replace('/^.+\?/U', '', $auth);
            if ($mode == 'url' && $query != $auth) {

                parse_str($query, $param); //解析规则中的param
                $intersect = array_intersect_assoc($request, $param);
                $auth = preg_replace('/\?.*$/U', '', $auth);
                if (in_array($auth, $name) && $intersect == $param) {  //如果节点相符且url参数满足
                    $list[] = $auth;
                }
            } else if (in_array($auth, $name)) {
                $list[] = $auth;
            }
        }
        if ($relation == 'or' && !empty($list)) {
            return true;
        }
        if ($relation == 'and' && empty(array_diff($name, $list))) {
            return true;
        }
        return false;
    }

    /**
     * 获得权限列表
     * @param integer $uid  用户id
     * @param integer $type 类型
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getAuthList($uid, $type) {
        static $_authList = []; //保存用户验证通过的权限列表
        $t = implode(',', (array) $type);
        if (isset($_authList[$uid . $t])) {
            return $_authList[$uid . $t];
        }
        if ($this->session->get('_auth_list_' . $uid . $t)) {
            //return $this->session->get('_auth_list_' . $uid . $t);
        }

        $groups = $this->getGroups($uid);//读取用户所属用户组
        if ($groups===false){
            return false;
        }
        $ids = []; //保存用户所属用户组设置的所有权限规则id
        foreach ($groups as $v) {
            $ids = array_merge($ids, explode(',', trim($v['rules'], ',')));
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            $_authList[$uid . $t] = [];
            return false;
        }

        $map = [
            ['type','in',$type],
            ['status','=', 1],
            ['id','in', $ids],
        ];
        $rules = Db::name('auth_rule')->where($map)->field('condition,name')->select();//读取用户组所有权限规则
        $authList = [];
        foreach ($rules as $rule) {//循环规则，判断结果。
            if (!empty($rule['condition'])) { //根据condition进行验证
                $user = $this->getUserInfo($uid); //获取用户信息,一维数组
                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
                $condition=null;
                eval('$condition=(' . $command . ');');
                $condition &&  $authList[] = strtolower($rule['name']);
            } else {
                $authList[] = strtolower($rule['name']); //只要存在就记录
            }
        }
        $_authList[$uid . $t] = $authList;
        $this->session->set('_auth_list_' . $uid . $t, $authList); //规则列表结果保存到session
        return array_unique($authList);
    }

    /**
     * 根据用户id获取用户组,返回值为数组
     * @param   int   $uid  用户id
     * @return array       用户所属的用户组 [
     *     ['uid'=>'用户id','group_id'=>'用户组id','title'=>'用户组名称','rules'=>'用户组拥有的规则id,多个,号隔开'),
     *     ...)
     */
    public function getGroups($uid) {
        static $groups = [];
        if (isset($groups[$uid])) {
            return $groups[$uid];
        }
        $user_groups = Db::view('auth_group_access', 'uid,group_id')
            ->view('auth_group', 'title,rules', "auth_group_access.group_id=auth_group.id")
            ->where('uid',$uid)
            ->where('status',1)
            ->select();
        return $user_groups ?: false;
    }

    /**
     * 获得用户资料,根据自己的情况读取数据库
     * @param $uid 用户ID
     * @return mixed
     */
    private function getUserInfo($uid) {
        static $userinfo = [];
        $user = Db::name('member');
        $_pk = is_string($user->getPk()) ? $user->getPk() : 'user_id';// 获取用户表主键
        if (!isset($userinfo[$uid])) {
            $userinfo[$uid] = $user->where($_pk, $uid)->field('*')->find();
        }
        return $userinfo[$uid];
    }

}