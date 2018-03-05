<?php
namespace Common\Controller;
use Think\Controller;
/**
* 
*/
class CommonController extends Controller
{
    protected $RxData;
    protected $out;
    protected $config;
    
    public function _initialize( $check=true )
    {
        if( C('IS_TEST') )
            header('Access-Control-Allow-Origin:*');
        
        $ret = [ 'status' => E_TOKEN, 'errstr' => '' ];
        $err = 0;
        $this->RxData = \Common\RXJson( $err );
        switch ($err) {
            case UPLOAD_FILES:
                //当前为文件上传
                $this->RxData =I("post.");
                break;

            case JSON_DECODE_ERR:
                //json 解析失败
                $this->retReturn( [ 'status' => E_JSON, 'errstr' => '' ] );
                break;

            default:
                break;
        }

        $action = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
        $actionArr = C('TOKEN_NOCHK_ACTION');
        if( in_array( $action, $actionArr ) )
            $check = false;

        if( $this->RxData['token'] ){

            \Common\ValidDaTokenFile( $this->RxData['token'], $user );

            $this->out = $user;
        }

        if( $check && ( !isset( $user ) || empty( $user ) || empty( $user['uid'] ) ) ){
            $this->retReturn( $ret );
        }

    }


    /*
    *@  自定义封装结果返回
    */
    public function retReturn( $ret ){
        if( ( is_array( $ret ) || is_object( $ret ) ) && isset( $ret['status'] ) && isset( $ret['errstr'] ) && empty( $ret['errstr'] ) )
            \Common\GenStatusStr( $ret['status'], $ret );

        $this->ajaxReturn( $ret );
    }



}
