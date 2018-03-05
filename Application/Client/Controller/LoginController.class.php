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
                $column = 'uid,user,pass,name,sex,birthday,icon,phone,address,qq,city,level,department,teacher_uid,invite_url,invite_n,invite_qr,invite_code,roles';
                $where = ['user' =>$username,'phone'=>$username,'_logic'=>'or'];
                $result = $this->m_m->findClient( '', $where );
            }

            // 判断账号是否存在及密码是否正确
            if( !$result || \Common\GetRealPass( $password ) != $result['pass'] ){
                $ret['status'] = E_USER;
                goto END;
            }

            $login_time = $this->m_m->saveClient( $where, ['login_time'=>time()] );
            var_dump(M()->getlastsql());die;

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


}
