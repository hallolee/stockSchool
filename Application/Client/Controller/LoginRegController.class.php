<?php
namespace Client\Controller;

class LoginRegController extends GlobalController {

    protected $m_m;
    protected $m_m1;

    public function _initialize( $check=false ){
        parent::_initialize( $check );
        $this->m_m = D('Client/Profile');
        $this->m_m1 = D('Client/BasicInfo');
    }


    /*
    * 发送短信验证码
    */
    public function sendSms(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_DATA, 'errstr' => '' ];

        if( !isset( $raw['phone'] ) || !is_numeric( $raw['phone'] ) )
            goto END;

        //生成四位数验证码，不足四位用0补齐
        $code = rand(0,9999);
        $len = strlen( $code );
        if($len==1){
            $code ="000".$code;
        }else if($len==2){
            $code ="00".$code;
        }else if($len==3){
            $code ="0".$code;
        }

        $d = array(
            'minor' => 1,
            'phone' => $raw['phone'],
            'key1'  => $code,
        );
        $z = \Common\sendSms( $d );
        //$ret['sms'] = $z;

        S( ['type'=>'memcached'] );
        $z = S( $raw['phone'], $code );

        $ret['status'] = E_OK;
        $ret['errstr'] = "";
END:
        $this->retReturn($ret);
    }


    /**
    * 用户登录函数
    */
    public function login(){
        $ret = [ 'status' => E_SYSTEM, 'token' => '', 'errstr' => '' ];
        $raw = $this->RxData;

        //登录方式判断
        // $reg_type = [ REG_PASSWD, REG_WECHAT, REG_PHONE, REG_COMPLEX ];
        $reg_type = [ REG_COMPLEX ];
        if( !isset( $raw['type'] ) || !in_array( $raw['type'], $reg_type ) )
            goto END;

        $where = [];
        switch ($raw['type']) {
            case REG_PASSWD:

                //验证参数( 必填 )
                $keys = [ 'user', 'pass' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                        goto END;

                    ${$val} = $raw[ $val ];
                }

                //生成条件
                $where['user'] = $user;
                $where['pass'] = \Common\GetRealPass( $pass );
                break;

            case REG_WECHAT:

                //验证参数( 必填 )
                $keys = [ 'code' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                        goto END;

                    ${$val} = $raw[ $val ];
                }

                //获取 openid 与 access_token
                $wechat = \Common\getOpenid( $code );
                if( $wechat['openid'] == '' ){
                    goto END;
                }

                //生成条件
                $where['openid'] = $wechat['openid'];

                break;

            case REG_PHONE:

                //验证参数( 必填 )
                $keys = [ 'phone' ,'phonecode' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                        goto END;

                    ${$val} = $raw[ $val ];
                }

                //验证短信验证码
                S( ['type'=>'memcached'] );
                $code = S( $phone );
                if( C('IS_TEST') && $phonecode == '1111' ){
                    $code = 'true';
                }
                else if( !$code ){
                    //验证码已过期或已失效
                    $ret[ 'status' ] = E_SMSCODE2;
                    goto END;
                }
                else if( $code != $phonecode ){
                    //验证码错误
                    $ret[ 'status' ] = E_SMSCODE;
                    goto END;
                }
                S( $phone, null );

                //生成条件
                $where['phone'] = $phone;
                break;

            case REG_COMPLEX:

                //验证参数( 必填 )
                $keys = [ 'user' ,'pass' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                        goto END;

                    ${$val} = $raw[ $val ];
                }

                //生成条件
                $where[] = [
                    'phone'     => $user,
                    'xiaozhu'   => $user,
                    '_logic'    => 'or'
                ];
                $where['pass'] = \Common\GetRealPass( $pass );

                break;

            default:
                goto END;
        }

        //获取用户信息
        $res = $this->m_m->findClient( $column, $where );

        if( !$res ){
            $ret['status'] = E_PASS;
            goto END;
        }
        //

        //生成登录token
        $token = \Common\GenDaTokenFile( $res );
        $login_time = $this->m_m->saveClient( $where, ['login_time'=>time()] );

        $ret[ 'status' ] = E_OK;
        $ret[ 'errstr' ] = '';
        $ret[ 'token' ] = $token;

END:
        $this->retReturn( $ret );
    }


    /**
    * 用户注册函数
    */
    public function reg() {
        $ret = [ 'status' => E_SYSTEM, 'token' => '', 'errstr' => '' ];
        $raw = $this->RxData;

        // 判断是否开放注册
        $reg_deny = $this->m_m1->findBasicInfo( 'value', [ 'field' => 'reg_type', 'module' => 'site' ] );
        if( !$reg_deny ) goto END;
        switch ($reg_deny['value']) {
            case '3':
                $ret['status'] = E_REG_DENY;
                $ret['errstr'] = 'reg all denyed';
                goto END;
                break;

            case '2':
                if(!$raw['scan']){
                    $ret['status'] = E_REG_ONLY_CODE;
                    $ret['errstr'] = 'reg only scan';
                    goto END;
                }
                break;

            case '1':
                $reg = true;
                break;

            default:
                goto END;
                break;
        }

        //注册方式判断
        // $reg_type = [ REG_PASSWD, REG_WECHAT, REG_PHONE, REG_COMPLEX ];
        $reg_type = [ REG_COMPLEX ];
        if( !isset( $raw['type'] ) || !in_array( $raw['type'], $reg_type ) )
            goto END;

        //验证额外参数( 可填可不填 )
        $other_key = [ 'qq', 'nickname' ];
        $other = [];
        foreach ($other_key as $val) {
            $other[ $val ] = '';
            if( isset( $raw[ $val ] ) && !empty( $raw[ $val ] ) )
                $other[ $val ] = $raw[ $val ];
        }

        switch ($raw['type']) {
            case REG_PASSWD:

                //验证参数( 必填 )
                $keys = [ 'user', 'pass' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                        goto END;

                    ${$val} = $raw[ $val ];
                }

                //验证账号是否已存在
                $where['user'] = $user;
                $user = $this->m_m->findClient( 'user', $where );
                if( $user ){
                    $ret['status'] = E_EXIST;
                    goto END;
                }

                $user = [
                    'user'      => $user,
                    'pass'      => \Common\GetRealPass( $pass ),
                    'openid'    => '',
                ];

                foreach ($other as $key => $value) {
                    $user[ $key ] = $value;
                }
                break;

            case REG_WECHAT:

                //验证参数( 必填 )
                $keys = [ 'code' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                        goto END;

                    ${$val} = $raw[ $val ];
                }

                //获取 openid 与 access_token
                $wechat = \Common\getOpenid( $code );
                if( $wechat['openid'] == '' ){
                    goto END;
                }

                //验证当前 openid 是否已注册
                $where['openid'] = $wechat['openid'];
                $user = $this->m_m->findClient( '', $where );
                if( $user ){
                    $ret['status'] = E_EXIST;
                    goto END;
                }

                //显示授权时，获取用户的微信信息
                // $wechat_user = \Common\getWeChatInfo( $wechat['access_token'], $wechat['openid'] );

                $user = [
                    'user'      => '',
                    'pass'      => '',
                    'openid'    => $wechat['openid'],
                    'phone'     => '',
                ];

                foreach ($other as $key => $value) {
                    $user[ $key ] = $value;
                }
                break;

            case REG_PHONE:

                //验证参数( 必填 )
                $keys = [ 'phone', 'phonecode' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                        goto END;

                    ${$val} = $raw[ $val ];
                }

                //验证短信验证码
                S( ['type'=>'memcached'] );
                $code = S( $phone );
                if( C('IS_TEST') && $phonecode == '1111' ){
                    $code = 'true';
                }
                else if( !$code ){
                    //验证码已过期或已失效
                    $ret[ 'status' ] = E_SMSCODE2;
                    goto END;
                }
                else if( $code != $phonecode ){
                    //验证码错误
                    $ret[ 'status' ] = E_SMSCODE;
                    goto END;
                }
                S( $phone, null );

                //验证账号是否已存在
                $where['phone'] = $phone;
                $user = $this->m_m->findClient( 'user', $where );
                if( $user ){
                    $ret['status'] = E_EXIST;
                    goto END;
                }

                $user = [
                    'user'      => $phone,
                    'pass'      => '',
                    'openid'    => '',
                    'phone'     => $phone,
                ];

                foreach ($other as $key => $value) {
                    $user[ $key ] = $value;
                }
                break;

            case REG_COMPLEX:

                //验证参数( 必填 )
                $keys = [ 'phone', 'phonecode', 'pass', 'question_id', 'question', 'answer', 'name' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                        goto END;

                    ${$val} = $raw[ $val ];
                }

                //验证短信验证码
                S( ['type'=>'memcached'] );
                $code = S( $phone );
                if( C('IS_TEST') && $phonecode == '1111' ){
                    $code = 'true';
                }
                else if( !$code ){
                    //验证码已过期或已失效
                    $ret[ 'status' ] = E_SMSCODE2;
                    goto END;
                }
                else if( $code != $phonecode ){
                    //验证码错误
                    $ret[ 'status' ] = E_SMSCODE;
                    goto END;
                }
                S( $phone, null );

                //验证账号是否已存在
                $where['phone'] = $phone;
                $where['_logic'] = 'or';
                $where['user'] = $user;
                $user_res = $this->m_m->findClient( 'user,phone', $where );
                if( $user_res['user'] && $user_res['user'] == $user ){
                    $ret['status'] = E_USER_EXIST;
                    goto END;
                }else if( $user_res['phone'] && $user_res['phone'] == $phone ){
                    $ret['status'] = E_PHONE_EXIST;
                    goto END;
                }

                $user = [
                    'user'          => $phone,
                    'pass'          => \Common\GetRealPass( $pass ),
                    'openid'        => '',
                    'phone'         => $phone,
                    'question_id'   => $question_id,
                    'question'      => $question,
                    'answer'        => md5($answer),
                    'name'          => $name,
                ];

                foreach ($other as $key => $value) {
                    $user[ $key ] = $value;
                }

                break;

            default:
                goto END;
        }

        //验证是否有邀请用户，邀请用户是否存在
        $invitecode = isset( $raw['invite_code'] )?$raw['invite_code']:'';
        $inviteuser = [];
        if( $invitecode ){
            $inviteuser = $this->m_m->findClient( 'uid,user,name', [ 'invite_code' => $invitecode ] );
            if( !$inviteuser ){
                $ret['status'] = E_INVITE;
                goto END;
            }
        }

        //注册用户信息
        $res = $this->m_m->addClient( [ $user, $inviteuser ] );

        if( !$res )
            goto END;

        //生成登录token
        $uinfo = array_merge( $res, $user );
        $token = \Common\GenDaTokenFile( $uinfo );

        $ret[ 'status' ] = E_OK;
        $ret[ 'errstr' ] = '';
        $ret[ 'token' ] = $token;

END:
        $this->retReturn( $ret );
    }


    /*
    * 验证toeken 是否有效
    */
    public function checkToken(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_TOKEN, 'errstr' => '' ];

        \Common\CheckDaTokenFile( $raw['token'], $out );

        if( isset( $out['uid'] ) && !empty( $out['uid'] ) ){
            $ret[ 'status' ] = E_OK;
            $ret[ 'time' ] = $out['etime'];

        }

        $this->retReturn( $ret );
    }

    /*
    * 验证toeken 是否有效
    */
    public function Logout() {
        $raw = $this->RxData;

        $ret = [
            'status' => E_OK,
            'errstr' => 'No Login or Loginout Fail',
        ];

        if( !$raw['token'] ) goto END;

        \Common\ValidDaTokenFile( $raw['token'], $out );
        if( empty( $out ) ) goto END;

        \Common\ValidDaTokenWrite( [], $raw['token'] );
        $ret[ 'errstr' ] = '';

END:
        $this->retReturn( $ret );
    }


    public function questionList(){
        $ret = [];

        $res = $this->m_m->selectQuestion( 'id,title question', [ 'status' => 1 ] );
        if( $res )
            $ret = $res;

END:
        $this->retReturn( $ret );
    }


    public function showUserQuestion(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '', 'data' => [] ];

        if( !isset( $raw['user'] ) || empty( $raw['user'] ) )
            goto END;

        //生成条件
        $where[] = [
            'phone'     => $raw['user'],
            'xiaozhu'   => $raw['user'],
            '_logic'    => 'or'
        ];

        //验证账号是否已存在
        $user = $this->m_m->findClient( 'question_id,question', $where );
        if( !$user || empty( $user['question_id'] ) ){
            $ret['status'] = E_NOEXIST;
            goto END;
        }

        $ret['status'] = E_OK;
        $ret['date'] = $user;

END:
        $this->retReturn( $ret );
    }



    public function resetPwd(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        //找回密码方式判断
        $reset_type = [ 1,2 ];
        if( !isset( $raw['type'] ) || !in_array( $raw['type'], $reset_type ) )
            goto END;

        $type = $raw['type'];
        $d = [];
        switch ($type) {
            case 1:

                //验证参数( 必填 )
                $keys = [ 'user', 'phonecode', 'pass' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                        goto END;

                    ${$val} = $raw[ $val ];
                }

                //验证短信验证码
                S( ['type'=>'memcached'] );
                $code = S( $user );
                if( C('IS_TEST') && $phonecode == '1111' ){
                    $code = 'true';
                }
                else if( !$code ){
                    //验证码已过期或已失效
                    $ret[ 'status' ] = E_SMSCODE2;
                    goto END;
                }
                else if( $code != $phonecode ){
                    //验证码错误
                    $ret[ 'status' ] = E_SMSCODE;
                    goto END;
                }
                S( $user, null );

                //生成条件
                $where['phone'] = $user;
                break;

            case 2:

                //验证参数( 必填 )
                $keys = [ 'user', 'question_id', 'answer', 'pass' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                        goto END;

                    ${$val} = $raw[ $val ];
                }

                //生成条件
                $where[] = [
                    'phone'     => $user,
                    'xiaozhu'   => $user,
                    '_logic'    => 'or'
                ];
                $where['question_id'] = $question_id;
                break;

            default:
                goto END;
        }

        //验证账号是否已存在
        $user = $this->m_m->findClient( 'uid,answer', $where );
        if( !$user ){
            $ret['status'] = E_NOEXIST;
            goto END;
        }else if( $type == 2 && $user['answer'] != md5( $answer ) ){
            $ret['status'] = E_USER;
            goto END;
        }

        $where['uid'] = $user['uid'];
        $d['pass'] = \Common\GetRealPass( $pass );
        $res = $this->m_m->saveClient( $where, $d );

        if( $res !== false )
            $ret[ 'status' ] = E_OK;
END:
        $this->retReturn( $ret );
    }


}
