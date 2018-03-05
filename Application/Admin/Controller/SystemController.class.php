<?php
namespace Admin\Controller;

class SystemController extends GlobalController{
    protected $system;

    public function _initialize($check = true){

        parent::_initialize($check);
        $this->system = D('System');
    }

    public function showConfig(){
        $ret = [];
        $head = C('PREURL');
        $result = $this->system->selectBasic('field,value', ['module' => BASIC_SITE]);
        foreach ($result as $v) {
            $ret[$v['field']] = $v['value'];
        }
        $ret['site_logo'] = $head . $ret['site_logo'];
        $ret['site_qr_code'] = $head . $ret['site_qr_code'];
END:
        $this->retReturn($ret);
    }


    public function editConfig(){
        $raw = $this->RxData;
        $ret = [];
        $head = C('PREURL');
        $keys = [
            'reg_type','site_qq','site_name','site_logo','site_phone','site_qr_code',
            'site_email','site_address','site_records','site_welcome','site_status'
        ];

        foreach ($keys as $v){
            if( isset( $raw[ $v ] ) && !empty( $raw[ $v ] ) )
                $data[$v] = $raw[$v];
        }
        foreach($data as $k=>$v){
            $result = $this->system->editBasic(['value'=>$v],['field'=>$k]);
        }

        $ret['status'] = 0;
        $ret['errstr'] = '';
END:
        $this->retReturn($ret);
    }

    public function upload(){
        $re = [];
        $uid = $this->out['uid'];

        $head = C('PREURL');

        $field = 'picture';
        $pre_path = C("UPLOAD_PATH");
        $realpath = $pre_path.'profile'."/".'uid_'.$uid.'/icon/';

        $conf = array(
            'pre' => 'pro',
            'types' => ['jpg', 'gif', 'png', 'jpeg'],
        );

        if( !is_dir($realpath) ) $z = mkdir( $realpath, 0775, true );
        $upload_res = \Common\_Upload($field,$realpath,$conf);

        if( $upload_res['state'] != 0 ){
            $re =  json_encode($upload_res);
            goto END;
        }


        foreach ($upload_res['file'] as $key => $value) {

            $file_path = $value['savepath'].$value['savename'];
            $path = $realpath.$value['savename'];
        }


        $re[ 'status' ] = 0;
        $re[ 'errstr' ] = '';
        $re[ 'url' ] = $head.$path;
        $re[ 'path' ] = $path;
END:
        $this->retReturn( $re );
    }


    /**
     * edit roles
     */

    public function editRoleGroup(){
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $raw = $this->RxData;

        $keys_m = [ 'name'];
        $keys = [ 'name', 'status', 'rem'];

        foreach($keys_m as $v) {
            if (!isset($raw[$v])) {
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
        }

        foreach ($keys as $val) {
            if (isset($raw[$val]))
                $data[$val] = $raw[$val];

        }
        if( $raw['id'] ){
            $exist = $this->system->findRoleGroup( '',['id'=>$raw['id']]);
            if( !$exist )
                goto END;
            $res = $this->system->saveRoleGroup($data , [ 'id'=>$raw['id']]);
            if( $res === false )
                goto END;

        }else{
            $res = $this->system->addRoleGroup( $data );
            if( !$res )
                goto END;

            $ret['id'] = $res;
        }

        $ret['status'] = E_OK;
END:
        $this->retReturn($ret);
    }


    public function showRoleList(){
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $raw = $this->RxData;
        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num  = $raw['page_limit'] ? $raw['page_limit'] : C('PAGE_LIMIT');
        $limit = $num * ($page-1).','.$num;


        $data = $this->system->selectRoleGroup( '', '', $limit,'id' );
        if(!$data)
            goto END;

        $count = $this->system->selectRoleGroup( 'count(*) total');

        $ret['page_n'] = count($data);
        $ret['page_start'] = $page;
        $ret['total'] = $count[0]['total'];
        $ret['data'] = $data;

END:
        $this->retReturn( $ret );
    }



    public function showLevelList(){
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];
        $raw = $this->RxData;
        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num  = $raw['page_limit'] ? $raw['page_limit'] : C('PAGE_LIMIT');
        $limit = $num * ($page-1).','.$num;
        $res = D('Profile')->selectLevel('','',$limit);
        if( $res )
            $ret['data'] = $res;

        $this->retReturn( $ret );
    }


    public function editLevel()
    {
        $raw = $this->RxData;

        $keys   = ['name','rem','extend'];
        $keys_m = ['name'];
        foreach($keys_m as $v) {
            if (!isset($raw[$v])) {
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
        }

        foreach ($keys as $v){
            if( isset( $raw[$v] )  )
                $data[$v] = $raw[$v];
        }

        $data['atime'] = time();


        if($raw['id']){
            $exist = $this->system->findLevel('', ['id'=>$raw['id']]);
            if(!$exist){
                $ret['status'] = E_NOEXIST;
                $ret['error']  = '';
                goto END;
            }

            $res = $this->system->editLevel($data, ['id'=>$raw['id']]);

        }else{
            $res = $this->system->addLevel($data);

            if(!$res){
                $ret['status'] = E_SYSTEM;
                $ret['error']  = '';
                goto END;
            }

        }

        $ret['status'] = E_OK;
        $ret['error']  = '';

END:
        $this->retReturn($ret);
    }


}