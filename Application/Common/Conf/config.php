<?php

/*
* common
*/
$c[ 'DB_TYPE' ]               = 'mysql';
// 服务器地址
$c[ 'DB_HOST' ]               = 'localhost';
// 数据库名
$c[ 'DB_NAME' ]               = '';
// 用户名
$c[ 'DB_USER' ]               = '';
// 密码
$c[ 'DB_PWD' ]                = '';
// 端口
$c[ 'DB_PORT' ]               = '3306';
// 数据库表前缀
$c[ 'DB_PREFIX' ]             = '';

// groups
// 项目分组设定,多个组之间用逗号分隔,例如'Home,Admin'
$c[ 'APP_GROUP_LIST' ]        = 'Admin,Client,Home';
// 默认分组
$c[ 'DEFAULT_GROUP' ]         = 'Admin';

//项目分组代号
$c[ 'APP_NUMBER' ] = [
    '101'           => 'Admin',
    '102'           => 'Client',
    '103'           => 'Home',
];

// path
$c[ 'upload_path' ] = 'uploads/';
$c[ 'upload_own_path' ] = 'uploads_own/';

// 不检查登录模式的方法
$c[ 'TOKEN_NOCHK_ACTION' ] = [ ];
// psd_salt
$c[ 'PSD_SALT' ] = 'cnzc';

// include
// 加载扩展配置文件
$c[ 'LOAD_EXT_CONFIG' ] = 'dev,constant,errcode,errstr,crons';
//加载扩展函数文件
//$c[ 'LOAD_EXT_FILE' ]   = 'ifunction';


//http or https
$c['SCHEME'] = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https' : 'http';


$c['CRON_CONFIG_ON'] = true; // 是否开启自动运行
$c['CRON_CONFIG'] = [
    'crons' => array('Client/Profile/crons', '5', '')
];

return $c;
?>
