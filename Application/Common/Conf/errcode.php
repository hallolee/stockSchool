<?php
//RXJson error code
const PARAM_OK          = 0;
const UPLOAD_FILES      = 1;
const JSON_DECODE_ERR   = 2;

//system error code
const E_OK          = 0;        //成功
const E_SYSTEM      = 1;        //系统出错
const E_RUN         = 2;        //执行失败
const E_CONF        = 3;        //配置出错
const E_MYSQL       = 4;        //SQL出错
const E_DATA        = 5;        //参数错误
const E_USER        = 6;        //验证用户信息失败
const E_PASS        = 7;        //验证用户密码(登录失败)
const E_TOKEN       = 8;        //Token不存在(登录失败)
const E_SESS        = 9;        //session有错(登录失败)
const E_SMSWECHAT   = 10;       //发送微信出错
const E_SMSPHONE    = 11;       //发送短信出错
const E_EXIST       = 12;       //某结果已存在
const E_NOEXIST     = 13;       //某结果不存在
const E_AC          = 14;       //权限不足
const E_STATUS      = 15;       //所处状态不符
const E_UPLOAD      = 16;       //上传文件失败
const E_SMSCODE     = 17;       //短信验证码错误
const E_SMSCODE2    = 18;       //短信验证码超时或丢失
const E_OPENID      = 19;       //微信openid错误
const E_NOCHANGE    = 20;       //没有数据变动
const E_DISABLE     = 21;       //账号被禁用
const E_INVITE      = 22;       //邀请码错误
const E_NOTENOUGH   = 23;       //不足、不够、未达标
const E_JSON        = 24;       //json 格式错误
const E_LIMIT       = 25;       //达到上限、极限
const E_FILE_TYPE   = 26;       //文件格式错误


const E_QQ_REG       =  10001;       //QQ已被注册
const E_QQ_NOT_EXIST =  10002;       //QQ未入群
const E_USER_EXIST   =  10003;       //用户名已被注册
const E_PHONE_EXIST  =  10004;       //手机号已被注册

const E_REG_DENY        = 10011;       //不允许注册
const E_REG_ONLY_CODE   = 10012;       //仅允许扫码注册

const E_ISSELF      = 10007;    //属于自己不可操作
const E_WORD        = 10029;    //含有敏感词汇

const E_ROLES        =  10020;       //角色不符
const E_CHECKED      =  10090;       //已审核的数据
const E_CHECKING     =  10093;       //正在审核的数据

const E_SECOND       =  20003;       //还有下级存在
const E_NAV_FIRST_LIMIT  =  20011;       //最大导航第一栏达到上限
const E_NAV_SECOND_LIMIT =  20010;       //最大导航第二栏达到上限
const E_NAV_STATUS       =  20013;       //导航状态不符

const E_ROLE = 10020;       //角色不符

const E_HWK_SUBMIT     = 10080;    //已提交的作业
const E_HWK_DEAD_LINE  = 10081;    //已超过截止日期



