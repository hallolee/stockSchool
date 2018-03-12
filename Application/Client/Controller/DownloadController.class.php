<?php

namespace Client\Controller;

class DownloadController extends GlobalController
{


    protected $m_m;
    protected $modelUser;
    protected $model;

    public function _initialize($check = true)
    {
        parent::_initialize($check = false);
        $this->modelUser = D('Admin/User');
        $this->model     = D('Admin/Resource');

        //get 获取token 验证登录
        if( !isset( $this->out['uid'] ) || empty( $this->out['uid'] ) || !is_numeric( $this->out['uid'] ) ){
            $ret = [ 'status' => E_TOKEN, 'errstr' => '' ];
            $raw = I('get.');
            if( !isset( $raw['token'] ) ){
                $this->retReturn( $ret );
            }else{
                \Common\ValidDaTokenFile( $raw['token'], $user );
                if( !isset( $user ) || empty( $user ) || empty( $user['uid'] ) ){
                    $this->retReturn( $ret );
                }else{
                    $this->out = $user;
                }
            }
        }


    }

    public function attachDownload()
    {
        $raw = I('get.')?I('get.'):$this->RxData;

        $ret = [];
        if(!$raw['id']){
            $ret['status'] = E_DATA;
            $ret['errstr'] = 'wrong params';
            goto END;
        }

        $attach_info = $this->model->findAttach('',['id'=>$raw['id']]);
        if(!$attach_info){
            $ret['status'] = E_NOEXIST;
            $ret['errstr'] = 'attach not exists';
            goto END;
        }


        $file_info = $this->model->findFile('role_download',['id'=>$attach_info['pid']]);

        //可阅读该文件的 的角色
        $roles = explode(',',$file_info['role_download']);

        if($file_info['role_download'] == ',-1,' || $this->out['group_id'] == -1)
            goto STP;


        //可下载该文件的 的角色
        if(!(end($roles)))
            array_pop($roles);
        if(!(reset($temp)))
            array_shift($roles);

        //用户的角色
        $user_role_info = D('Admin/AuthRule')->selectAuthGroupAccess('',['uid'=>$this->out['uid']]);

        $group_id = [];
        foreach($user_role_info as $v){
            $group_id[] = $v['group_id'];
        }

        $cd = array_intersect ($roles,$group_id);
        if(!$cd)
            goto END;


STP:
        $Http = new \Org\Net\Http;
        $file_dir = $attach_info['path'];
        $Http::download($file_dir);


END:
        $this->retReturn($ret);
    }


}