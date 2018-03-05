<?php
namespace Admin\Controller;
use Common\Controller\CommonController;
class GlobalApiController extends CommonController {

    public function _initialize( $check=false )  {
        parent::_initialize( $check );
        // $check = CheckLogin();
        // if($check != E_OK){
        //     $this->redirect('Backend/Index/top_jump');
        //     exit();
        // }

        // $auth_group_access =  M(TAUTH_GROUP_ACC);
        // $auth_group =  M(TAUTH_GROUP);
        // $CheckAuth = E_SYSTEM;

        // $where['uid'] = session('userid');
        // $group = $auth_group_access->where($where)->find();

        // $admin = $auth_group->field('id')->where([ 'rules' => '0' ])->find();

        // if( $group['group_id'] == $admin['id'] ){
        //     $CheckAuth = E_OK;
        // }else{
        //     $CheckAuth = CheckAuth($where['uid']);
        // }

        // if($CheckAuth != E_OK){
        //     $data = array('status'=>'9');
        //     $this->ajaxReturn($data);
        //     exit();
        // }
    }

}
