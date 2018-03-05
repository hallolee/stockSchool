<?php

//Token写入方式参数
const TOKEN_APPEND = 1;
const TOKEN_COVER = 2;

//Token读取验证模式参数
const TOKEN_FULLCHECK = 1;
const TOKEN_NOCHECK = 2;

//注册方式
const REG_PHONE     = 1;    //手机验证码
const REG_PASSWD    = 2;    //账号密码
const REG_WECHAT    = 3;    //wechat openid
const REG_COMPLEX   = 4;    //复合信息（手机验证码+账号密码）


//数据表常量,按表首字母排序
const TAD                   = 'ad';
const TAUTH_RULE            = 'auth_rule';
const TAUTH_GROUP           = 'auth_group';
const TAUTH_GROUP_ACC       = 'auth_group_access';

const TBASIC_INFO           = 'basic_info';

const TCLIENT_M             = 'client_menu';
const TCHECK                = 'checking';
const TCHECK_E              = 'check_ext';

const TCOURSE               = 'course';
const TCOURSE_C             = 'course_cat';
const TCOURSE_M             = 'course_member';

const TDEPART               = 'department';

const TFILE                 = 'file';
const TFILE_ATTACH          = 'file_attach';
const TFILE_REVIEW          = 'file_review';
const TFILE_V_K             = 'file_video_key';

const TFORUM                 = 'forum';
const TFORUM_A               = 'forum_attach';
const TFORUM_C               = 'forum_class';
const TFORUM_C_A             = 'forum_class_attach';
const TFORUM_C_R             = 'forum_class_review';
const TFORUM_I               = 'forum_img';
const TFORUM_R               = 'forum_review';


const THWK                  = 'homework';
const THWK_A                = 'homework_attach';
const THWK_R                = 'homework_review';
const THWK_RECORD           = 'homework_record';
const THWK_T                = 'homework_tags';
const THWK_S                = 'homework_subject';


const TMESSAGE              = 'message';


const TINFO                 = 'info';
const TINFO_A               = 'info_activity';
const TINFO_E               = 'info_expert';
const TINFO_I               = 'info_img';

const TNAV                  = 'nav';

const TNOTICE_R             = 'notice_recv';

const TQQLIST               = 'qq_list';

const TREFER                = 'refer';
const TROLE                 = 'role_group';
const TROLE_USER            = 'role_group_access';

const TSYSTEM_INFO          = 'system_info';
const TSYSTEM_INFO_TYPE     = 'system_info_type';

const TUSER                 = 'user';
const TUSER_CLASS_R         = 'user_class_record';
const TUSER_COURSE_R        = 'user_course_record';
const TUSER_LEVEL           = 'user_level';
const TUSER_Q               = 'user_question';
const TUSER_T               = 'user_teacher';
const TUSER_W_O             = 'user_wallet_order';
const TUSER_APPLY_REC       = 'user_apply_record';
const TUSER_APPLY_ATT       = 'user_apply_attach';
const TUSER_APPLY_TEA_REC   = 'user_apply_teacher_record';
const TUSER_UPGRADE_REC     = 'user_upgrade_record';
const TUSER_CHGTEA_REC      = 'user_chg_teacher_record';
const TUSER_CHG_STATUS_REC  = 'user_chg_status_record';

const TVIDEO_KEY            = 'video_key';

//用户账号状态
const U_NORMAL  = 1;    //正常
const U_DISABLE = 2;    //禁用


//basic_info  module keys
const BASIC_SITE = 'site';
const BASIC_SYS  = 'sys';
const BASIC_SEO  = 'seo';


//广告类型
const SINGLE           = 1;     //小广告
const BANNER           = 2;     //轮播
const REG_PAGE         = 3;     //注册页
const FLINK            = 4;     //友情链接

//status
const OK   = 0;


//系统信息
const SYS_SITE_CLOSE = 0;                //关闭网站
const SYS_INFO_ABOUT = 1;                //关于我们
const STATUS_ON      = 1;               //已发布
const STATUS_OFF     = 2;               //未发布


const SYS_OK     = 1;   //正常
const SYS_FORBID = 2;   //禁用
const SYS_PRESET = 3;   //预置

//中文常量
const AUTHOR = '佚名';                    //作者
const SOURCE = '量学小筑';                //来源

//审核
const LINE       = 1;         //待审核
const PASS       = 2;         //通过
const REJECT     = 3;         //拒绝
const DELETE     = 4;         //删除


const ADD       =   1;
const MODIFY    =   2;

const C_UPDATE     = 1;      //更新记录表数据标志


const C_INC_QUOTA  = 1;      //教员名额+1
const C_DEC_QUOTA  = 2;      //教员名额-1
const C_RTIME      = 3;      //入学
const C_BACK_STATUS= 4;      //拒绝时撤回状态


const C_CHECK_SYS = '自动审核';    //资源

const C_RES       = 1;    //资源
const C_FORUM     = 2;    //论坛
const C_APP_STU   = 3;    //申请学员
const C_CHG_TE    = 4;    //变更教员
const C_APP_TE    = 5;    //升级教员
const C_UPGRADE   = 6;    //学员升星
const C_BACK_TE   = 7;    //恢复教员

const C_BREAK_TE  = 6;    //休教
const C_QUIT_TE   = 7;    //退教
const C_BACK_TE   = 7;    //恢复教员
const C_BACK_STU  = 8;    //退教

//系统默认参数
const DEFAULT_LEVEL = 1;        //默认level_id
const DEFAULT_DEP   = 1;        //默认department_id
const DEFAULT_ROLE  = 1;        //默认角色id


const ITEM_URL = 'columnList.html?type=';

//导航
const NAV_SYS    = 1;         //系统导航
const NAV_USER   = 2;         //自定义导航
const MAX_FIRST  = 5;         //最大导航第一栏按钮数量
const MAX_SECOND = 10;        //最大导航第二栏按钮数量


const MAX_S_NAV = 5;          //最大二级导航按钮数量
const TOUR_ON   = 1;          //游客可见
const TOUR_OFF  = 2;          //游客不可见

const JUMP_OWN = 1;           //内链
const JUMP_OUT = 2;           //外链
const JUMP_SECOND = 3;        //二级导航

const NAV_ON  = 1;             //开启
const NAV_OFF = 2;             //关闭

const NAV_FIRST  = 1;             //导航位置第一栏
const NAV_SECOND = 2;             //导航位置第二栏

const DEF_PID       = 0;      //顶级pid

//资源
const FilE_BOOK       = 1;      //书籍
const FILE_DOC        = 2;      //文档
const FILE_VIDEO      = 3;      //视频


//系统预置角色
const ROLE_SU       = 1;      //超管
const ROLE_TE       = 2;      //教员
const ROLE_STU      = 3;      //学员
const ROLE_NOR      = 4;      //普通成员


//在线作业

const HWK_SUMMIT     = 1;      //已提交
const HWK_UNSUMMIT   = 2;      //未提交

const HWK_EXPIRE     = 1;      //已过期
const HWK_OK         = 2;      //未过期

const HWK_DID        = 1;      //已完成
const HWK_UNDO       = 2;      //未完成
const HWK_UNCOT      = 3;      //待批阅
const HWK_NOT_REV    = 4;      //未领取
const HWK_REV        = 5;      //已领取
const HWK_EXIPRE     = 6;      //已过期


const HWK_L_OK       = 1;      //已批阅
const HWK_L_UNCOT    = 2;      //未批阅
const HWK_L_REV      = 3;      //未完成
const HWK_L_UNREV    = 4;      //未领取

//教员
const HWK_T_UNCOT    = 4;       //待批阅
const HWK_T_COT      = 5;       //批阅完成
const HWK_T_CANCEL     = 6;       //已取消

const HWK_SUB = 1;             //已提交

const SYSTEM_QUOTA = 1;             //已提交


const UNLOGIN            = 0;      //游客
const ROLE_TEACHER       = 1;      //教员
const ROLE_STUDENT       = 2;      //学员
const ROLE_NORMAL        = 3;      //普通成员

//db
const ROLE_DB_NOR         = 1;      //普通成员
const ROLE_DB_STU         = 2;      //学员
const ROLE_DB_TEA         = 3;      //教员

//二进制编号
const ROLE_BIN_NOR         = 1;      //普通成员
const ROLE_BIN_STU         = 2;      //学员
const ROLE_BIN_TEA         = 4;      //教员


//用户状态
const STUDY_BREAK        = 2;      //休学
const STUDY_QUIT         = 3;      //退学


const TEACH_BREAK        = 2;      //休教
const TEACH_QUIT         = 3;      //退教


const CHG_LINE           = 4;      //待审核


const CHG_QUIT_S        = 1;      //退学
const CHG_BREAK_S       = 2;      //休学
const CHG_BREAK_T       = 3;      //休教
const CHG_QUIT_T        = 4;      //退教
const CHG_BACK_T        = 5;      //恢复教员
const CHG_BACK_S        = 6;      //恢复学员

const TIE   = 1;    //绑定教员
const UNTIE = 2;    //解绑教员

//通知
const M_FORUM = 1;      //贴子
const M_RESOURCE = 2;   //资源
const M_HWK= 3;         //作业



