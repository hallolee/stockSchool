<?php
namespace Client\Controller;

class IndexController extends GlobalController
{

    public function _initialize( $check=false ){
        parent::_initialize( $check );
    }


    public function getConf(){
        $wechat_config = \Common\GetWechatTuples();

        $ret['APPID'] = $wechat_config['app_id'];
        $ret['SERVER'] = C('PREENT');

        $this->ajaxReturn( $ret );
    }

}