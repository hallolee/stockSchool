<?php

/*
* @ common 公共配置
*/
 //数据库名
$c[ 'DB_NAME' ]  = 'test_stock_school';
// 用户名
$c[ 'DB_USER' ]  = 'lbys';
// 密码
$c[ 'DB_PWD' ]   = '123456';
//数据库报错、调试模式
$c[ 'DB_DEBUG' ] = true;


//memcached 缓存服务器
$c[ 'MEMCACHED_SERVER' ] = [
    [ '127.0.0.1', '11211', '0' ],
];
//缓存有效时间
$c[ 'DATA_CACHE_TIME' ] = 900;

//修改电话号码验证与修改操作最长间隔时间
$c[ 'EDIT_PHONE_CACHE_TIME' ] = 1800;

// token 存放位置
$c[ 'TOKEN_PATH' ] = 'token/';
// token 有效时间( measured by second )
$c[ 'DATOKEN_EXPIRE' ] = 3600*24;

// 项目名
$c[ 'APPNAME' ]     = 'StockSchool';
//链接头部
$c[ 'PREURL' ]      = C('SCHEME').'://'.$_SERVER['HTTP_HOST'].'/'.$c['APPNAME']."/";
//项目链接
$c[ 'PREENT' ]      = $c['PREURL']."index.php/";

//发送短信的 API接口
$c[ 'SMS_API' ]         = 'http://uno_srv/wechat/cgi-bin/sms_new';

//wechat
$c[ 'WECHAT_TUPLE_FILE' ] = '!lbguard';
$c[ 'WECHAT_ACCESSTOKEN_FILE' ] = '!lbguard';

$c[ 'WXCONF_URL' ]  = 'http://uno_srv/wechat/cgi-bin/wxconf';


// admin--user--pass--group_id
$c[ 'ADMIN_USER' ] = 'su';
$c[ 'ADMIN_PASS' ] = 'e935656cc69183c2ace6db093316c517';
$c[ 'ADMIN_GROUP' ] = '-1';


//开启测试环境
$c[ 'IS_TEST' ] = true;


//在线作业
$c[ 'EXPIRE_HWK' ]  = 1001444;      //作业有效期
$c[ 'EXPIRE_SUB' ]  = 2;            //作业逾期提醒
$c[ 'ANALYSIS' ]    = 86400*10;     //作业开始统计时间

//缓存有效期
$c[ 'EXPIRE_CACHE' ] = 86400*3;      //缓存日期


$c[ 'TEACHER_QUOTA' ] = 99;          //教员默认名额
$c[ 'PAGE_LIMIT' ]    = 10000;       //默认单页面数量

return $c;
?>
