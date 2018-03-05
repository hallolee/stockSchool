<?php

namespace Common;

/*
* 获取json格式数据
* @ $err 错误码提示
*/
function RXJson( &$err ){
    $err = PARAM_OK;
    //是否为上传
    if( $_FILES ){ $err = UPLOAD_FILES; return ''; }

    //参数是否为空
    $input  = file_get_contents('php://input');
    if( !$input ) return '';

    //解析是否成功
    $raw = json_decode($input,true,'512',JSON_BIGINT_AS_STRING);
    if( $raw === null || $raw === false ) $err = JSON_DECODE_ERR;

    return $raw;
}


/*
生成token文件，返回tokenid
*/
function GenDaTokenFile( $d=[], $module=MODULE_NAME )
{
    $prefixed = C( 'TOKEN_PREFIXED' );

    $app_number = C('APP_NUMBER');
    $app = array_search($module,$app_number);
    if( !$app ) return false;

    $now = GetMicroTime();
    $sign = md5( $now.rand(1000,9999) );
    $name = $app.$sign;

    $dir = C( 'TOKEN_PATH' ).$module.'/';
    $file = $dir.$prefixed.$name;

    $p = [
        'file'      => [
            'btime'     => time(),
            'etime'     => time()+C('DATOKEN_EXPIRE'),
        ],
        'data'      => $d,
    ];

    if ( !is_dir( $dir )) @mkdir( $dir, 0777, ture );
    if(!file_exists($file)) touch($file);
    if( $p ) file_put_contents($file,json_encode( $p ));

    $token = $name;
    return $token;
}

/*
* @ $verify 检查token所在模块与当前模块 TOKEN_FULLCHECK 全查 ，NO_CHECK 不查
取出token文件
*/
function ValidDaTokenFile( $token, &$out='', $verify=TOKEN_FULLCHECK, $module=MODULE_NAME )
{
    $prefixed = C( 'TOKEN_PREFIXED' );
    $key = substr($token, 0,3);

    $app_number = C('APP_NUMBER');
    $token_module = isset($app_number[ $key ])?$app_number[ $key ]:'';

    if( $verify == TOKEN_NOCHECK )
    {
        $module = $token_module;
    }else if( !$token_module || $token_module != $module ){
        return false;
    }

    $dir = C( 'TOKEN_PATH' ).$module.'/';
    $file = $dir.$prefixed.$token;

    if( !is_file( $file ) )
        return false;

    $token_body = json_decode( file_get_contents( $file ), true );

    if( $token_body['file']['etime'] < time() ){
        unlink($file);
        return false;
    }

    $out = $token_body['data'];
    return true;
}


/*
* @ $d 存储数据
* @ $token 文件标识
* @ $write 写入方式 TOKEN_APPEND 追加，TOKEN_COVER 覆盖
* @ $verify 检查token所在模块与当前模块 TOKEN_FULLCHECK 全查 ，NO_CHECK 不查
*取出token文件,写入文件
*/
function ValidDaTokenWrite($d = [], $token, $write=TOKEN_COVER, $verify=TOKEN_FULLCHECK, $module=MODULE_NAME )
{
    $prefixed = C( 'TOKEN_PREFIXED' );
    $key = substr($token, 0,3);

    $app_number = C('APP_NUMBER');
    $token_module = isset($app_number[ $key ])?$app_number[ $key ]:'';

    if( $verify == TOKEN_NOCHECK )
    {
        $module = $token_module;
    }else if( !$token_module || $token_module != $module ){
        return false;
    }

    $dir = C( 'TOKEN_PATH' ).$module.'/';
    $file = $dir.$prefixed.$token;

    if( !is_file( $file ) )
        return false;

    $token_body = json_decode( file_get_contents( $file ), true );

    if( $token_body['file']['etime'] < time() ){
        unlink($file);
        return false;
    }

    if( $write == TOKEN_COVER ){
        $token_body['data'] = $d;
    }else if( $write == TOKEN_APPEND ){
        $token_body['data'] = array_merge($token_body['data'],$d);
    }

    $status = file_put_contents( $file, json_encode( $token_body ) );

    if ( !$status ) {
        return false;
    }
    return true;
}


/*
* 读取token文件
* @ $token 文件标识
* @ $module 当前所在模块
* @ $verify 检查token所在模块与当前模块 TOKEN_FULLCHECK 全查 ，NO_CHECK 不查
*/
function ReradDaTokenFile( $token, $module, $verify )
{

    $prefixed = C( 'TOKEN_PREFIXED' );
    $key = substr($token, 0,3);

    $app_number = C('APP_NUMBER');
    $token_module = isset($app_number[ $key ])?$app_number[ $key ]:'';

    if( $verify == TOKEN_NOCHECK )
    {
        $module = $token_module;
    }else if( !$token_module || $token_module != $module ){
        return false;
    }

    $prefixed = C( 'TOKEN_PREFIXED' );
    $dir = C( 'TOKEN_PATH' ).$module.'/';
    $file = $dir.$prefixed.$token;

    if( !is_file( $file ) )
        return false;

    $token_body = json_decode( file_get_contents( $file ), true );

    if( $token_body['file']['etime'] < time() ){
        unlink($file);
        return false;
    }

    return $token_body;
}


/*
* 取出token文件
* @ $token 文件标识
* @ $verify 检查token所在模块与当前模块 TOKEN_FULLCHECK 全查 ，NO_CHECK 不查
* @ $module 当前所在模块
*/
function CheckDaTokenFile( $token, &$out='', $verify=TOKEN_FULLCHECK, $module=MODULE_NAME )
{

    $token_body = \Common\ReradDaTokenFile( $token, $module, $verify );

    if( $token_body === false )
        return false;

    $out['uid'] = $token_body['data']['uid'];
    $out['etime'] = $token_body['file']['etime'];
    return true;
}


/*
* 更新token文件
* @ $token 文件标识
* @ $verify 检查token所在模块与当前模块 TOKEN_FULLCHECK 全查 ，NO_CHECK 不查
* @ $module 当前所在模块
*/
function ReplaceDaTokenFile( $token, &$out='', $verify=TOKEN_FULLCHECK, $module=MODULE_NAME )
{
    $prefixed = C( 'TOKEN_PREFIXED' );
    $dir = C( 'TOKEN_PATH' ).$module.'/';
    $token_body = \Common\ReradDaTokenFile( $token, $module, $verify );

    if( $token_body === false )
        return false;

    $token_new = \Common\GenDaTokenFile( $token_body['data'] );

    if( !$token_new )
         return false;

    unlink($dir.$prefixed.$token);

    $file = $dir.$prefixed.$token_new;

    if( !is_file( $file ) )
        return false;

    $token_body = json_decode( file_get_contents( $file ), true );

    $out['etime'] = $token_body['file']['etime'];
    return $token_new;
}


/*
*获取毫秒级时间戳
*/
function GetMicroTime()
{
    $micro = microtime( true );
    return floor( $micro * 1000 );
}


/*
* 补全返回完整链接
* @ $url 需要判断的链接
*/
function GetCompleteUrl( $url='' )
{
    // 判空
    if( empty( $url ) ) return '';

    // 判断 http 是否存在且所在位置是否为头部
    if( stripos( $url, 'http' ) === 0 ) return $url;

    return C('PREURL').$url;
}


/*
@公共上传
*/
function _Upload( $module='', $path='', $conf='' ){                              //公共上传方法
    $module = $module == "" ? 'file' : $module; //未知模块将存入file文件夹

    if (!is_dir($path)) @mkdir( $path, 0775, true );
    import("ORG.Net.UploadFile");
    $upload = new \Think\Upload();
    $upload->rootPath = $path;
    $upload->maxSize = 9145728;
    $upload->autoSub  = false;           //是否创建时间子目录
    $upload->allowExts = $conf['types'];

    if(!empty($conf['savename'])){
        $upload->saveName = $conf['savename'];                  //保存自定义名称
    }elseif(!empty($conf['pre']) ){
        $upload->saveName = array('uniqid',$conf['pre']);       //图片名前缀
    }else{
        $upload->saveName = '';                                 //保存默认名称
    }
    $upload->savePath = '';
    $upload->uploadReplace = true;

    $res = $upload->upload();

    if (!$res) {

        $info = array(
            "status" => E_UPLOAD,
            "msg" => $upload->getError()
        );
        return $info;
    } else {

        foreach ($res as $value) {
            $value['savepath'] = $path.$value['savepath'];
            $cache[] = $value;
        }

        $info['file'] = $cache;
        $info['status'] = E_OK;

        return $info;
    }
}

/*
@生成缩略图
*/
function _Thumb( $file_path='', $file_name='', $width='150', $height='150' ){ //生成缩略图的公共方法

    $image = new \Think\Image();
    $image->open($file_path);

    $name = str_replace( strstr($file_name, '.') ,"" ,$file_name );
    $path = str_replace($file_name,"",$file_path);

    $type = substr(strrchr($file_name, '.'), 1);
    $new_name = $name."_thumb.".$type;

    // 生成150*150的缩略图并保存为thumb.jpg
    $res = $image->thumb($width,$height)->save("$path/".$new_name);

    if (!$res) {

        $info = array(
            "status" => E_UPLOAD,
            "msg" => $upload->getError()
        );
        return $info;
    } else {

        $info['path'] = "$path/".$new_name;
        $info[ 'savename' ] = $new_name;
        $info['status'] = E_OK;

        return $info;
    }
}


/**
 * 生成对应状态提示
 * @param $errcode,$ret
 * @return $ret
 */
function GenStatusStr( $errcode, &$ret )
{
    $errstrArr = C('ERRSTR');
    $errstr = isset( $errstrArr[ $errcode ] )?$errstrArr[ $errcode ]:'No desc';

    $ret['status'] = $errcode;
    $ret['errstr'] = $errstr;

    return true;
}

/*
生成存入数据库的password
*/
function GetRealPass($password) {
    $pass = md5(C("PSD_SALT").$password);
    return $pass;
}

/**
* 随机字符
* @param number $length 长度
* @param string $type 类型
* @param number $convert 转换大小写
* @return string
*/
function random($length=6, $type='string', $convert=0) {
    $config = array(
        'number'=>'1234567890',
        'letter'=>'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'string'=>'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789',
        'all'=>'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
    );

    if(!isset($config[$type])) $type = 'string';
    $string = $config[$type];
    $code = '';
    $strlen = strlen($string) -1;
    for($i = 0; $i < $length; $i++){
        $code .= $string{mt_rand(0, $strlen)};
    }
    $code = $convert ? strtoupper($code) : strtolower($code);
    return $code;
}




/* Log Begin */
function Dmsg( $func, $msg )
{
    $info = "===== In $func(): $msg =======";
    trace( $info );
}

function Dexp( $func, $msg )
{
    $info = "===== In $func(): =======\n";
    $info .= var_export( $msg, true );

    trace( $info );
}
/* Log End */


/*
* post method
*/
function post( $url, $postdata ) {

    $httph =curl_init($url);
    curl_setopt($httph, CURLOPT_POST, 1);//设置为POST方式
    curl_setopt($httph, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($httph, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($httph, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($httph, CURLOPT_TIMEOUT, 1);   //超时退出，新增1
    curl_setopt($httph, CURLOPT_HEADER,0);
    $result=curl_exec($httph);
    //\Think\Log::write( $result );
    if (curl_errno($httph)) {      //错误提示，新增2
       $result =  'Error-'.curl_error($httph);
    }
    curl_close($httph);

    return $result;
}



/**
 * 发送短信
 */
function sendSms( $d )
{
    // if( !C('push') ) return;

    $url  = C( 'SMS_API' );
    if( !isset($d['major']) ) $d[ 'major' ] = 1;
    $data = json_encode( $d );

    //Dmsg( __FUNCTION__, $url );
    $result = post( $url, $data );
    //$res = json_decode($result,true);
    $res = $result;

    return $res;
}



/*
删除文件夹
*/
function deldir( $dir ) {
   //先删除目录下的文件：
    $dh=opendir( $dir );
    while ( $file = readdir( $dh )) {
        if( $file !="." && $file !="..") {
            $fullpath = $dir."/".$file;
            if(!is_dir( $fullpath )) {
                 unlink( $fullpath );
             } else {
                 deldir( $fullpath );
             }
         }
    }

   closedir( $dh );
   //删除当前文件夹：
    if(rmdir( $dir )) {
        return true;
    } else {
        return false;
    }
 }





/*
* Get wechat openid
*/
function getOpenid( $code='' ){
    $ret = [];
    if( !$code ) return $ret;

    $m_wechat_conf = GetWechatTuples();

    $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$m_wechat_conf['app_id']."&secret=".$m_wechat_conf['app_secret']."&code=".$code."&grant_type=authorization_code";
    $result_str = UrlGet($url);
    $re_array = json_decode($result_str,true);

    if( !isset($re_array['openid']) )
    {
        Dexp( __FUNCTION__, $re_array );
        return $ret;
    }

    $ret = $re_array;

    return $ret;
}



/*
* Get wechat user info
*/
function getWeChatInfo( $access_token='',$openid='' ){
    $ret = [];
    if( !$access_token || !$openid ) return $ret;

    $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid;

    $result_str = UrlGet($url);
    $re_array = json_decode($result_str,true);

    if( !isset($re_array['openid']) )
    {
        Dexp( __FUNCTION__, $re_array );
        return $ret;
    }

    // if( $re_array['headimgurl'] ) Download($re_array['headimgurl'],'download/file','icon_','jpg');
    $ret = $re_array;

    return $ret;
}



/*
* Get wechat config params in url mode
*/
function GetWechatConfFromUrlByName( $public_name )
{
    $ret = [];
    if( !$public_name ) goto END;

    $url = C( 'WXCONF_URL' );
    $d = [
        'name' => $public_name,
    ];
    $d = json_encode( $d, JSON_UNESCAPED_UNICODE );
    $z = Post( $url, $d );
    $z = json_decode( $z, true );

    if( !$z || !isset($z['access_token']) || !$z['access_token'] )
    {
        Dexp( __FUNCTION__, $z );
        goto END;
    }
    $ret = $z;

END:
    return $ret;
}


/*
* Get wechat 4 tuples in either way
*/
function GetWechatTuples( $conf_name='WECHAT_TUPLE_FILE' )
{
    $ret = '';
    $atfile = C( $conf_name );
    if( !$atfile )
    {
        Dmsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }

    if( $atfile[0] == '!' )
    {
        // url mode
        $public_name = substr( $atfile, 1 );
        $z = GetWechatConfFromUrlByName( $public_name );

        if( !$z ) goto END;
        $ret = [
            'app_id'     => $z[ 'appid' ],
            'app_secret' => $z[ 'appsecret' ],
            'mch_id'     => $z[ 'mchid' ],
            'mch_secret' => $z[ 'mchsecret' ],
        ];

        goto END;
    }

    // file mode as default
    if( !file_exists($atfile) )
    {
        Dmsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }
    $ret = file_get_contents( $atfile );

END:
    return $ret;
}

/*
* Get wechat access token & js ticket in either way
*/
function GetWechatTT( $conf_name='WECHAT_ACCESSTOKEN_FILE' )
{
    $ret = '';
    $atfile = C( $conf_name );
    if( !$atfile )
    {
        Dmsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }

    if( $atfile[0] == '!' )
    {
        // url mode
        $public_name = substr( $atfile, 1 );
        $z = GetWechatConfFromUrlByName( $public_name );

        if( !$z ) goto END;
        $ret = [
            'access_token' => $z[ 'access_token' ],
            'js_ticket'    => $z[ 'js_ticket' ],
        ];

        goto END;
    }

    // file mode as default
    if( !file_exists($atfile) )
    {
        Dmsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }
    $ret = file_get_contents( $atfile );

END:
    return $ret;
}

/*
* Get wechat access token in either way
*/
function GetWechatAccessToken( $conf_name='WECHAT_ACCESSTOKEN_FILE' )
{
    $ret = '';
    $atfile = C( $conf_name );
    if( !$atfile )
    {
        Dmsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }

    if( $atfile[0] == '!' )
    {
        // url mode
        $public_name = substr( $atfile, 1 );
        $z = GetWechatConfFromUrlByName( $public_name );

        if( !$z ) goto END;
        $ret = $z[ 'access_token' ];

        goto END;
    }

    // file mode as default
    if( !file_exists($atfile) )
    {
        Dmsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }
    $ret = file_get_contents( $atfile );

END:
    return $ret;
}

/*
* Get wechat js ticket in either way
*/
function GetWechatJsTicket( $conf_name='WECHAT_ACCESSTOKEN_FILE' )
{
    $ret = '';
    $atfile = C( $conf_name );
    if( !$atfile )
    {
        Dmsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }

    if( $atfile[0] == '!' )
    {
        // url mode
        $public_name = substr( $atfile, 1 );
        $z = GetWechatConfFromUrlByName( $public_name );

        if( !$z ) goto END;
        $ret = $z[ 'js_ticket' ];

        goto END;
    }

    // file mode as default
    if( !file_exists($atfile) )
    {
        Dmsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }
    $ret = file_get_contents( $atfile );

END:
    return $ret;
}


/*
 * Create necessary config params for using wechat js api.
 * In either way, all from url or all from local
 */
function GetWechatJsApiConf( $url, $conf_name='WECHAT_ACCESSTOKEN_FILE' )
{
    $ret = [];

    if( !$url ) goto END;

    $atfile = C( $conf_name );
    if( !$atfile )
    {
        Dmsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }

    if( $atfile[0] == '!' )
    {
        // url mode
        $public_name = substr( $atfile, 1 );
        $z = GetWechatConfFromUrlByName( $public_name );
        if( !$z ) goto END;
        Dexp( __FUNCTION__, $z );

        $timestamp = time();
        $noncestr  = CreateNonceStr();
        $ticket    = $z[ 'js_ticket' ];

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$ticket&noncestr=$noncestr&timestamp=$timestamp&url=$url";
        $signature = sha1( $string );

        $ret = [
            'app_id'    => $z[ 'appid' ],
            'timestamp' => $timestamp,
            'nonceStr'  => $noncestr,
            'signature' => $signature,
        ];

        goto END;
    }

    // file mode as default
    if( !file_exists($atfile) )
    {
        Dmsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }
    $ret = file_get_contents( $atfile );

END:
    return $ret;
}


/*
*
*/
function GetWechatApi( $url, $data, $conf_name='WECHAT_ACCESSTOKEN_FILE' )
{
    $ret = [];

    $token = GetWechatAccessToken( $conf_name );
    if( !$token ) goto END;
    $url = C( 'WECHAT_API_URL' )."$url?access_token=$token";
    $d = json_encode( $data, JSON_UNESCAPED_UNICODE );
    $z = Post( $url, $d );
    $z = json_decode( $z, true );

    if( isset($z['errcode']) && isset($z['errmsg']) )
    {
        Dmsg( __FUNCTION__, "Get url '$url' failure!" );
        Dexp( __FUNCTION__, $z );
        goto END;
    }
    $ret = $z;

END:
    return $ret;
}

/**
 * 公共下载
 * 临时方法（tp默认download 配置出错）
 */

function Download_t($file_path)
{

    $file_dir = $file_path;
    //检查文件是否存在
    if (!file_exists( $file_dir)) {
        echo "文件找不到";
        exit ();
    } else {
        //打开文件
        $file = fopen( $file_dir, "r");
        //输入文件标签
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length: " . filesize($file_dir));
        Header("Content-Disposition: attachment; filename=" . end(explode('/',$file_path)));
        //输出文件内容
        //读取文件内容并直接输出到浏览器
        echo fread($file, filesize($file_dir ));
        fclose($file);
        exit ();
    }
}

/**
 * file delete
 */

function FileDelete($file)
{
    $res = false;
    if(file_exists($file))
        $res =  unlink($file);

    return $res;

}

/**
 * @return all levels
 */
function getLevelInfo(){
    $level_info = D('Client/Profile')->selectLevel('');
    $level = [];
    if($level_info)
        foreach($level_info as $v)
            $level[$v['id']] = $v;

    return $level;
}

/**
 *return users
 */

function getUserInfo($uid)
{
    $user_res = D('Client/Profile')->selectClient('uid,icon,name,xiaozhu,nickname,level_id,qq', ['uid' => ['in', $uid]]);
    $user = [];
    if ($user_res)
        $level = getLevelInfo();
        foreach ($user_res as $val) {
            $user[$val['uid']] = [
                'uid'   =>  $val['uid'],
                'name'  =>  $val['name'],
                'qq'    =>  $val['qq'],
                'level_id'  =>  $val['level_id'],
                'xiaozhu'  =>  $val['xiaozhu'],
                'level'    =>  $level[$val['level_id']]['name'],
                'nickname' =>  $val['nickname'],
                'icon'     =>  \Common\GetCompleteUrl($val['icon'])
            ];
        }

    return $user;
}


