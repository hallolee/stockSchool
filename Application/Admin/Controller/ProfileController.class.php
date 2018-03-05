<?php
namespace Admin\Controller;

class ProfileController extends GlobalController {

    protected $m_m;

    public function _initialize( $check=true ) {
        parent::_initialize( $check );
        $this->m_m = D('Profile');
        $this->m_m1 = D('Admin/AuthRule');
    }


    public function ShowInfo() {
        $info = [];

        $res = $this->out;
        if( $res['uid'] )
            $info = [
                'user'      => $res['user'],
                'nickname'  => $res['nickname'],
                'name'      => $res['name'],
                'sex'       => $res['sex'],
                'birthday'  => $res['birthday'],
                'phone'     => $res['phone'],
                'xiaozhu'   => $res['xiaozhu'],
                'icon'      => \Common\GetCompleteUrl($res['icon']),
                'invite_code'=> $res['invite_code'],
                'uid'               => $res['uid'],
                'address'           => $res['address'],
                'qq'                => $res['qq'],
                'city'              => $res['city'],
                'up_name'           => $res['up_name'],
                'up_phone'          => $res['up_phone'],
                'up_xiaozhu'        => $res['up_xiaozhu'],
                'up_nickname'       => $res['up_nickname'],
                'level'             => $res['level'],
                'department'        => $res['department'],
                'teacher'           => $res['teacher'],
                'invite_url'        => C('PREURL').$res['invite_code'],
                'invite_qr'         => $res['phone'],
                'roles'             => $res['roles']?explode(',',$res['roles']):''
            ];

        $this->retReturn( $info );
    }



    public function changePwd(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        $keys = [ 'old_pass', 'new_pass' ];
        foreach ($keys as $val) {
            if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                goto END;

            ${$val} = $raw[ $val ];
        }
        if( $new_pass == $old_pass ){
            $ret['status'] = E_NOCHANGE;
            goto END;
        }

        if( \Common\GetRealPass( $old_pass ) != $this->out['pass'] ){
            $ret['status'] = E_PASS;
            goto END;

        }

        $d['pass'] = \Common\GetRealPass( $new_pass );
        $res = $this->m_m->saveClient( [ 'uid' => $this->out['uid'] ], $d );

        if( $res !== false ){
            \Common\ValidDaTokenWrite( $d, $raw['token'], TOKEN_APPEND );
            $ret['status'] = E_OK;
        }
END:
        $this->retReturn( $ret );
    }



    //用于修改手机号码的用户信息验证
    public function checkBeforeEditPhone(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        if( !isset( $raw['type'] ) ) goto END;
        switch ($raw['type']) {
            case 1:

                //验证参数( 必填 )
                $phone = $this->out['phone'];
                $keys = [ 'phonecode' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) ){
                        $ret['status'] = E_DATA;
                        goto END;
                    }

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

                break;

            case 2:

                //验证参数( 必填 )
                $keys = [ 'question_id', 'answer' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) ){
                        $ret['status'] = E_DATA;
                        goto END;
                    }

                    ${$val} = $raw[ $val ];
                }

                $chk_res = $this->m_m->findClient( 'question_id,answer', [ 'uid' => $this->out['uid'] ] );
                if( $chk_res['question_id'] != $question_id || $chk_res['answer'] != md5( $answer ) ){
                    $ret['status'] = E_USER;
                    goto END;
                }

                break;

            default:
                $ret['status'] = E_DATA;
                goto END;
        }

        $ret['status'] = E_OK;
        S( 'Adminuid-'.$this->out['uid'], 1, C('EDIT_PHONE_CACHE_TIME') );
END:
        $this->retReturn( $ret );
    }

    // 修改手机号码
    public function editPhone(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $uid = $this->out['uid'];

        S( ['type'=>'memcached'] );
        if( !S( 'Adminuid-'.$this->out['uid'] ) )
            goto END;

        $keys = [  'phone', 'phonecode' ];
        foreach ($keys as $val) {
            if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) ){
                $ret['status'] = E_DATA;
                goto END;
            }

            ${$val} = $raw[ $val ];
        }

        if( $phone == $this->out['phone'] ){
            $ret['status'] = E_NOCHANGE;
            goto END;
        }

        //验证短信验证码;
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

        $chk_res = $this->m_m->findClient( 'uid', [ 'uid' => [ 'neq', $uid ], 'phone' => $phone  ] );
        if( $chk_res ){
            $ret['status'] = E_EXIST;
            goto END;
        }

        $d['phone'] = $phone;
        $d['user'] = $phone;
        $res = $this->m_m->saveClient( [ 'uid' => $uid ], $d );

        if( $res !== false ){
            \Common\ValidDaTokenWrite( $d, $raw['token'], TOKEN_APPEND );
            $ret['status'] = E_OK;
            S( 'Adminuid-'.$this->out['uid'], null );
        }
END:
        $this->retReturn( $ret );
    }

    public function showMenu(){                     //菜单列表
        $data = [];
        $uid = $this->out['uid'];

        //获取用户拥有的权限模块
        $rules = [];
        if( $uid == -1 && isset( $this->out['group_id'] ) && $this->out['group_id'] == C('ADMIN_GROUP') ){
            // 超级管理员 拥有全权限
            $group_where = [];
            $group_result = $this->m_m1->selectAuthRule( 'id', $group_where );

            foreach ($group_result as $value) {
                $rule_result[] = $value['id'];
            }

            $rules = implode(",", $rule_result);

        }else{
            // 验证用户所属模块， 获取相应权限
            $where['uid'] = $uid;
            $aga_result = $this->m_m1->selectAuthGroupAccess( '', $where );

            // group_id 为 1 时默认为全权限用户
            if( $aga_result[0]['group_id'] == 1 ){

                $group_where = [];
                $group_result = $this->m_m1->selectAuthRule( 'id', $group_where );

                foreach ($group_result as $value) {
                    $rule_result[] = $value['id'];
                }

                $rules = implode(",", $rule_result);
            }else{
                $group_id = [];
                foreach ($aga_result as $val) {
                    $group_id[] = $val['group_id'];
                }

                $group_where['id'] = [ 'in', $group_id ];
                $group_where['status'] =  1;
                $group_result = $this->m_m1->selectAuthGroup( '', $group_where );

                $rules = [];
                foreach ($group_result as $val) {
                    $rules = array_merge($rules, explode(',', trim($val['rules'], ',')));
                }

                $rules = array_unique($rules);   //用户拥有的权限
            }
        }

        $ar_where['id'] = array('in',$rules?$rules:'');
        $ar_where['category'] = array('exp',"!='api'");
        $ar_where['menu_title'] = array('exp',"!=''");
        $ar_result = $this->m_m1->selectAuthRule( '', $ar_where, '`order` asc ' );     //获取用户拥有的所有菜单

        $a = [];
        foreach ($ar_result as $value) {
            $a[] = $value['pid'];
        }

        $a = array_unique($a);//用户权限模块

        $set_where['category'] = 'title';
        $set_where['id'] = ['in',implode(',', $a)];
        $set_result = $this->m_m1->selectAuthRule( '', $set_where, '`order` asc ');       //获取相应的一级菜单

        foreach ($set_result as $value) {
            $id = $value['id'];

            $rest[$id]['title'] = $value['title'];
            $rest[$id]['icon'] = $value['icon'];
            if( !is_numeric( $value['name'] ) ){
                $rest[$id]['url'] = $value['name'];
            }
        }

        foreach ($ar_result as $value) {       //整理用户的权限菜单
            $id = $value['id'];

            if( $value['category'] == 'title' )
                continue;

            $url = $value['name'];

            $menu_title = $value['menu_title'];
            $class = $value['class'];
            $pid = $value['pid'];
            $menu[] = array('id'=> $id,'url'=> $url,'title'=> $menu_title,'pid'=> $pid,'class' => $class);
        }

        foreach ($menu as $key => $value) {   //输出相应用户能查看的菜单
            if(in_array($value['pid'],$a)){
                $id = $value['id'];
                $pid = $value['pid'];
                $rest[$pid]['submenu'][] = array('url'=>$value['url'],'title'=>$value['title']);
            }
        }

        foreach ($rest as $key => $value) {
            if(!empty($value['title'])){
                $data[] = $value;
            }
        }

        $this->retReturn($data);
    }


    //用户邀请的成员列表
    public function userInviteList()
    {
        $raw = $this->RxData;
        $ret = [];
        $page = $raw['page_start'] ? $raw['page_start'] - 1 : 0;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * $page . ',' . $num;

        $where['a.upline'] = $raw['uid']?$raw['uid']:$this->out['uid'];

        $head = C('PREURL');
        $colunm = 'b.uid,b.icon,b.name,c.name as level,b.phone,a.atime';

        $count = $this->m_m->selectInviteUser($colunm, $where, '');
        $result = $this->m_m->selectInviteUser($colunm, $where, $limit, 'a.atime desc');

        foreach($result as &$v){
            $v['icon'] = $v['icon']?C('PREURL').$v['icon']:'';
        }

        $ret['page_start'] = $page + 1;
        $ret['page_n'] = count($result);
        $ret['total'] = count($count);
        $ret['data'] = $result;
END:
        $this->retReturn($ret);
    }


    public function GetOpenid(){
        $info = [];
        $raw = $this->RxData;
        $code = $raw['code'];

        if( !$code )
            goto END;

        $info['openid'] = \Common\getOpenid($code);
END:
        $this->retReturn( $info );
    }


    public function ShowRefer() {
        $info = [];
        $uid = $this->out['uid'];

        $refer = $this->m_m->refer_get( 'uid,utype,atime', [ 'upline' => $uid, 'upline_type' => B_CLIENT ] );

        $utype = [
            B_CLIENT    => 'user_get_one',
            B_MERCHANT  => 'merchant_get_one',
            B_STAFF     => 'staff_get_one'
        ];

        $column = [
            B_CLIENT    => 'name,phone,sex,icon',
            B_MERCHANT  => 'name,contact_phone phone,contact_sex sex,icon',
            B_STAFF     => 'name,phone,sex,icon'
        ];

        $func = $utype[ $refer['utype'] ];

        if( !$func )
            goto END;

        $user = $this->m_m->$func( $column[ $refer['utype'] ], [ 'uid' => $refer['uid'] ] );

        if( !$user )
            goto END;

        $info = [
            "name"  => $user['name'],
            "sex"   => $user['sex'],
            "phone" => $user['phone'],
            "icon"  => $user['icon'],
            "atime" => $refer['atime']
        ];

END:
        $this->retReturn( $info );
    }

    public function ShowSolitaire() {
        $info = [
            'in_solitaire'  => 2,         // 1已参加接龙，2 未参加接龙
            'rank'          => '',
            'data'          => []
        ];

        $dragon_res = $this->m_m->dragon_detail_get();

        $info['total_buser'] = $dragon_res['total_buser'];
        $info['bonus'] = $dragon_res['bonus'];
        $info['quota'] = $dragon_res['quota'];
        $info['length'] = '0';

        $res = $this->m_m->dragon_ranking_get( '', [ 'uid' => $this->out['uid'] ], '`rank` ASC' );

        if( $res ){
            $info['in_solitaire'] = 1;
            foreach ($res as $key => $val) {
                $length = $val['rank']*$dragon_res['quota']-$dragon_res['total_user'];

                if( empty( $info['rank'] ) && $val['rank'] >= $info['total_buser'] ){
                    $info['rank'] = $val['rank'];
                    $info['length'] = $length>12?12:$length;
                }

                $info['data'][] = [
                    'rank'      => $val['rank'],
                    'status'    => ($val['status'] == '3')?$val['status']:'1',
                    'length'    => ($val['status'] == '3')?'0':$length,
                    'atime'     => $val['atime']
                ];
            }
        }

        $this->retReturn( $info );
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
        $data = ['icon'=>$path];
        $user_info = $this->m_m->findClient('icon',['uid'=>$this->out['uid']]);
        if(!empty($user_info['icon'] ))
            $del_file = \Common\FileDelete($user_info['icon'] );

        $res = $this->m_m->saveClient(['uid'=>$this->out['uid']],$data);

        if(!$res ){
            $re['status'] = 1;
            $re['errstr'] = '上传失败';
            goto END;
        }

        $re[ 'status' ] = 0;
        $re[ 'errstr' ] = '';
        $re[ 'url' ] = \Common\GetCompleteUrl($path);
        $re[ 'path' ] = $path;

END:
        $this->retReturn( $re );
    }



    /*
    * 修改用户信息
    */
    public function editInfo(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        $key = [ 'nickname','name','qq','phone','city' ];
        foreach ($key as $val) {
            if( isset( $raw[ $val ] ) && $raw[ $val ] !== '' )
                $d[ $val ] = $raw[ $val ];
        }

        if( !$d ) goto END;

        // qq 只能填一次，再次则不修改
        if( isset( $d['qq'] ) && !empty( $this->out['qq'] ) ){
            unset( $d['qq'] );
        }

        $res = $this->m_m->saveClient( [ 'uid' => $this->out['uid'] ], $d );

        // 当用户未修改信息提交，也算修改成功
        if( $res !== false )
            $ret['status'] = E_OK;

        \Common\ValidDaTokenWrite( $d, $raw['token'], TOKEN_APPEND );

END:
        $this->retReturn( $ret );
    }



    public function AddrShow() {
        $info = [];
        $raw = $this->RxData;
        $uid = $this->out['uid'];

        $column = 'id,name,phone,pcd,address';

        if( isset( $raw['id'] ) && is_numeric( $raw['id'] ) ){
            $where = [ 'id' => $raw['id'], 'status' => 0 ];
            $res = $this->m_m->GetAddr( $column, $where );

            if( $res )
                $info = $res[0];
        }else{
            $where = [ 'uid' => $uid, 'status' => 0 ];
            $res = $this->m_m->GetAddr( $column, $where );

            if( $res )
                $info = $res;
        }

        $this->retReturn( $info );
    }

    public function AddrDef() {
        $info = [];
        $column = 'id,name,phone,pcd,address';

        $where = [ 'uid' => $this->out['uid'], 'status' => 0 ];
        $res = $this->m_m->GetAddr( $column, $where );

        if( $res )
            $info = $res[0];

        $this->retReturn( $info );
    }


    public function AddrAdd() {
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $raw = $this->RxData;

        $keys = [ 'name','pcd','address','phone' ];
        foreach ($keys as $v) {
            if( !isset( $raw[ $v ] ) || empty( $raw[ $v ] ) )
                goto END;

            $data[ $v ] = $raw[ $v ];
        }
        $data['uid'] = $this->out['uid'];
        $data['status'] = 0;

        $res = $this->m_m->AddrAdd( $data );

        if(!$res)
            goto END;

        $ret['status'] = E_OK;
        $ret['id'] = $res;
END:
        $this->retReturn( $ret );
    }

    public function AddrEdit() {
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $raw = $this->RxData;

        if( !isset( $raw['id'] ) || empty( $raw['id'] ) )
            goto END;

        $keys = [ 'name','pcd','address','phone' ];
        foreach ($keys as $v) {
            if( isset( $raw[ $v ] ) && !empty( $raw[ $v ] ) )
                $data[ $v ] = $raw[ $v ];
        }

        $where['id'] = $raw['id'];
        $res = $this->m_m->AddrEdit( $where, $data );

        if( $res === false )
            goto END;

        $ret['status'] = E_OK;
END:
        $this->retReturn( $ret );
    }

    public function AddrDel() {
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $raw = $this->RxData;

        if( !isset( $raw['id'] ) || empty( $raw['id'] ) )
            goto END;

        $res = $this->m_m->AddrDel( [ 'id' => $raw['id'] ] );

        if( $res )
            $ret['status'] = E_OK;

END:
        $this->retReturn( $ret );
    }



}
?>
