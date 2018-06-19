<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2017/12/30
 * Time: 下午10:59
 */

return [
    'SERVER_NAME'=>"EasySwoole",
    'MAIN_SERVER'=>[
        'HOST'=>'0.0.0.0',
        'PORT'=>9501,
        'SERVER_TYPE'=>\EasySwoole\Core\Swoole\ServerManager::TYPE_WEB_SERVER,
        'SOCK_TYPE'=>SWOOLE_TCP,//该配置项当为SERVER_TYPE值为TYPE_SERVER时有效
        'RUN_MODEL'=>SWOOLE_PROCESS,
        'SETTING'=>[
            'task_worker_num' => 8, //异步任务进程
            'task_max_request'=>10,
            'max_request'=>5000,//强烈建议设置此配置项
            'worker_num'=>8
        ],
    ],
    'DEBUG'=>true,
    'TEMP_DIR'=>null,//若不配置，则默认框架初始化
    'LOG_DIR'=>null,//若不配置，则默认框架初始化
    'EASY_CACHE'=>[
        'PROCESS_NUM'=>1,//若不希望开启，则设置为0
        'PERSISTENT_TIME'=>0//如果需要定时数据落地，请设置对应的时间周期，单位为秒
    ],
    'CLUSTER'=>[
        'enable'=>false,
        'token'=>null,
        'broadcastAddress'=>['255.255.255.255:9556'],
        'listenAddress'=>'0.0.0.0',
        'listenPort'=>'9556',
        'broadcastTTL'=>5,
        'nodeTimeout'=>10,
        'nodeName'=>'easySwoole',
        'nodeId'=>null
    ],
    'TEMPLATE'=> [
        'view_path'  => EASYSWOOLE_ROOT . '/App/Views/',   # 模板文件目录
        'tpl_replace_string'  =>  [
            '__STATIC__'=>'/static',
            '__ADMIN__' => '/static/admin',
        ]
    ],
    'DATABASE' => [
        // 数据库类型
        'type'            => 'mysql',
        // 服务器地址
        'hostname'        => '127.0.0.1',
        // 数据库名
        'database'        => 'easy',
        // 用户名
        'username'        => 'root',
        // 密码
        'password'        => '',
        // 端口
        'hostport'        => '3370',
        // 数据库表前缀
        'prefix'          => 'tp5_',
        // 是否需要断线重连
        'break_reconnect' => true,
        //开启模型后缀
        'class_suffix' => true,
        //分页配置
        'paginate' => [
            'type'     => 'bootstrap',
            'var_page'  => 'page',
            'list_rows' => 15,
        ]
    ]

];