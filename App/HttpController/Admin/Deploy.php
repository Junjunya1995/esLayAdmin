<?php
/**
 * Description of Deploy.php.
 * Date: 2017-08-03 14:31
 */

namespace App\HttpController\Admin;


use App\Models\DeployModel;

class Deploy extends Admin {

    /**
     * 配置选项
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */
    public function index()
    {
        $this->fetch();
    }

    /**
     * 配置选项
     * @author staitc7 <static7@qq.com>
     * @param int $group 分组
     * @param int $page 页码
     * @param int $limit 每页条数
     * @return mixed
     */
    public function deployJson()
    {
        $data = $this->request()->getParsedBody();
        $page  = (int)$data['page']  ?? 1;
        $limit = (int)$data['limit'] ?? 10;
        $group = $data['group'] ?? 0;
        $map=[
            ['status','=',1],
            ['group',(int)$group ? '=':'>=', (int)$group?:0]
        ];
        $field = 'id,name,group,type,sort,area,title';
        $data  = $this->model('DeployModel')->listsJson($map, $field, 'sort asc', $page, $limit);
        return $this->layuiJson($data);
    }

    /**
     * 用户更新或者添加菜单
     */
    public function renew() {
        $Deploy = $this->app->model('Deploy');
        $info= $Deploy->renew();
        if ($info===false) {
            return $this->error($Deploy->getError());
        }
        Hook::listen('user_behavior', [
            'action' => 'update_config',
            'model' => 'Deploy',
            'record_id' => (int)$info['id'],
            'user_id'=>UserInfo::userId()
        ]);
        $this->app->cache->rm('system_config');
        return $this->success('操作成功', $this->app->url->build('Deploy/index'));
    }

    /**
     * 配置选项
     * @author staitc7 <static7@qq.com>
     * @param int $id 分组
     * @return mixed
     */
    public function group()
    {
        $id = $this->request()->getQueryParam('id') ?? 1;
        (int)$id || $this->error('参数错误');
        $field = 'id,name,title,extra,value,remark,type';
        $Model = $this->model('DeployModel');
        $data  = $Model->lists([['group','=',(int)$id],['status','=', 1]], $field, 'sort desc');
        $type  = [1=>'基本', 2=>'内容', 3=>'用户', 4=>'系统'];
        $this->fetch('' ,[
            'list' => $data,
            'group_id' => $id,
            'type' => $type,
            'metaTitle' => "{$type[$id]}设置"
        ]);
    }

    /**
     * 配置详情
     * @param int $id 菜单ID
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */
    public function edit($id = 0) {
        if ((int)$id > 0) {
            $info = $this->app->model('Deploy')->edit((int)$id);
        }
        return $this->setView(['info'=>$info ?? null,'metaTitle' => '配置详情']);
    }

    /**
     * 网站设置保存
     * @param null $config
     * @author staitc7 <static7@qq.com>
     */
    public function setUp($config = null) {
        if (!$config && !is_array($config)) {
            return $this->error('数据有误，请检查后在保存');
        }
        $Deploy = $this->app->model('Deploy');
        $info=$Deploy->batchSave($config);
        if ($info !== false) {
            $this->app->cache->rm('system_config');
            return $this->success('操作成功');
        } else {
            return $this->error($Deploy->getError());
        }
    }
}