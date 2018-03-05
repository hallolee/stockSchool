<?php
namespace Admin\Controller;

class LoginController extends GlobalController {

    protected $m_m;
    protected $m_m1;

    public function _initialize( $check=false ){
        parent::_initialize( $check );
        $this->m_m = D('Profile');
        $this->m_m1 = D('Admin/AuthRule');
    }


    public function Login(){                //检查登录信息是否正确
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $raw = $this->RxData;

        $username = $raw[ 'user' ];
        $password = $raw[ 'pass' ];

        if ( !empty( $username ) && !empty( $password ) ){

            if( $username == C('ADMIN_USER') ){
                // 超管账号直接存于程序配置中
                $result = [
                    'uid'         => -1,
                    'user'        => C('ADMIN_USER'),
                    'pass'        => C('ADMIN_PASS'),
                    'name'        => '超级管理员',
                    'sex'         => 1,
                    'birthday'    => '2018-1-8',
                    'icon'        => '',
                    'phone'       => '',
                    'address'     => '',
                    'qq'          => '',
                    'city'        => '',
                    'level'       => '',
                    'department'  => '',
                    'teacher_uid' => '',
                    'invite_url'  => '',
                    'invite_n'    => '',
                    'invite_qr'   => '',
                    'invite_code' => '',
                    'group_id'    => -1,
                    'roles'       => '超级管理员'
                ];

            }else{
                // 普通用户信息验证
                $where = ['xiaozhu' =>$username,'phone'=>$username,'_logic'=>'or'];
                $result = $this->m_m->findClient( '', $where );

                $teacher_info = $this->m_m->findClient( 'uid,nickname,name,phone', ['uid'=>$result['teacher_uid']] );
                $up_info      = $this->m_m->findUpline( 'a.nickname,a.name,a.phone,a.xiaozhu', ['b.uid'=>$result['uid']] );
                $level_info   = $this->m_m->findLevel( 'id,name', ['id'=>$result['level_id']] );

                $result['up_phone']    = $up_info['phone']?$up_info['phone']:'';
                $result['up_nickname'] = $up_info['nickname']?$up_info['nickname']:'';
                $result['up_name']     = $up_info['name']?$up_info['name']:'';
                $result['xiaozhu']     = $up_info['xiaozhu']?$up_info['xiaozhu']:'';

                $result['teacher']  = [
                    'uid' =>$teacher_info['uid']?$teacher_info['uid']:'',
                    'name'=>$teacher_info['name']?$teacher_info['name']:''
                ];
                $result['level']  = [
                    'id'  =>$level_info['id'],
                    'name'=>$level_info['name']
                ];
            }

        //     判断账号是否存在及密码是否正确
            if( !$result || \Common\GetRealPass( $password ) != $result['pass'] ){
                $ret['status'] = E_USER;
                goto END;
            }

            $login_time = $this->m_m->saveClient( $where, ['login_time'=>time()] );


            $token = \Common\GenDaTokenFile( $result );

            $ret[ 'token' ] = $token;
            $ret[ 'status' ] = E_OK;
            $ret[ 'errstr' ] = '';

        }
END:
        $this->retReturn( $ret );
    }


    //验证完登陆之后再验证权限
    public function checkToken(){
        $raw = $this->RxData;
        $ret = [
            'status' => E_TOKEN,
            'errstr' => 'NO Login or Timeout',
        ];

        // 验证token 是否有效
        \Common\ValidDaTokenFile( $raw['token'], $out );
        if( !isset( $out['uid'] ) || empty( $out['uid'] ) ) goto END;

        $ret[ 'status' ] = E_AC;
        $ret[ 'errstr' ] = '';

        // 验证页面参数是否存在
        if( isset( $raw['page'] ) && !empty( $raw['page'] ) ){

            // 超管直接跳过
            if( $out['uid'] == -1 && isset( $out['group_id'] ) && $out['group_id'] == C('ADMIN_GROUP') ){
                $ret[ 'status' ] = E_OK;
            }else if( $raw['page'] == 'index.html' ){
                // 过滤index.html 特殊页面
                $ret[ 'status' ] = E_OK;
            }else{

                $where['uid'] = $out['uid'];
                $group = $this->m_m1->FindAuthGroupAccess( '', $where );
                $admin = $this->m_m1->FindAuthGroup( 'id', [ 'rules' => '0' ] );

                // 用户权限为全权限的管理员组跳过检测
                if( $group['group_id'] == $admin['id'] ){
                    $CheckAuth = E_OK;
                }else{
                    $CheckAuth = CheckAuth( $where['uid'], $raw['page'] );
                }

                if( $CheckAuth == E_OK )
                    $ret[ 'status' ] = E_OK;
            }
        }
END:
        $this->retReturn( $ret );
    }


    public function Logout() {
        $raw = $this->RxData;

        $ret = [
            'status' => E_TOKEN,
            'errstr' => '',
        ];

        if( !$raw['token'] ) goto END;

        \Common\ValidDaTokenFile( $raw['token'], $out );
        if( empty( $out ) ){
            $ret[ 'status' ] = E_OK;
            goto END;
        }

        \Common\ValidDaTokenWrite( [], $raw['token'] );
        $ret[ 'status' ] = E_OK;

END:
        $this->retReturn( $ret );
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


}
