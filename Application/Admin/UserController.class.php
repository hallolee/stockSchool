<?php
namespace Admin\Controller;

class UserController extends GlobalController
{
    protected $model;
    protected $m_m;
    protected $m_m1;
    protected $modelUser;
    protected $modelClassroom;

    public function _initialize($check = false)
    {

        parent::_initialize($check = false);
        $this->model = D('Admin/Profile');
        $this->modelClassroom = D('Admin/Classroom');
        $this->modelUser = D('Admin/User');
        $this->m_m = D('Admin/Manage');
        $this->m_m1 = D('Admin/AuthRule');
    }

    public function userList()
    {
        $raw = $this->RxData;
        $ret = [];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:10;
        $limit = $num*($page-1).','.$num;
        $where['a.uid'] = ['neq',$this->out['uid']];
        $where = [];

        $keys = ['phone','name'];
        foreach ( $keys as $item) {
            if($raw[$item])
                $where['a.'.$item] = ['like','%'.$raw[$item].'%'];
        }
        if($raw['time_start'] && $raw['time_end']){
            $where['a.atime'] = [
                ['egt',$raw['time_start']],
                ['lt',$raw['time_end']]
            ];
        }elseif($raw['time_start'] ) {
            $where['a.atime'] = ['egt', $raw['time_start']];
        }elseif($raw['time_end'] ){
            $where['a.atime'] = ['lt',$raw['time_end']];
        }

        $column = [
            'a.uid,a.xiaozhu,a.nickname,a.status,a.name,a.phone,a.login_time,a.atime,
            a.invite_n,a.qq,a.roles as role,
            c.name as level_name,e.name as teacher_name'
        ];

        $order = ['a.atime desc'];

        $result = $this->modelUser->selectUser($column,$where,$limit,$order);

//        var_dump(M()->getlastsql());die;
        if(!$result)
            goto END;
        $count  = $this->modelUser->selectUser('',$where);


        foreach($result as &$v){
            if($v['role'])
                $v['role'] = explode(',',$v['role']);
        }
        $ret['total'] = count($count);
        $ret['page_start'] = $page;
        $ret['page_n'] = count($result);
        $ret['data'] = $result;

END:
        $this->retReturn( $ret );

    }


    //编辑用户教员

    public function editTeacher()
    {
        $raw = $this->RxData;
        $ret = [];

        $ret['status'] = E_SYSTEM;
        if(!is_array($raw['uids']))
            goto END;
        $teacher_info = D('Classroom')->selectTeacher('',['a.uid'=>$raw['teacher_uid'],'status'=>1]);
        if(!$teacher_info){
            $ret['errstr'] = 'not exist';
            goto END;
        }

        $user_info = D('Profile')->selectClient('uid,teacher_uid',['a.uid'=>['in',$raw['uids']]]);
        /*     if(count($user_info) < count($raw['uids'])){
                 $ret['status'] = 1;
                 $ret['errstr'] = 'uid not exist';
                 goto END;
             }*/


        if($teacher_info[0]['quota_left']<count($raw['uids'])){
            $ret['status'] = 1;
            $ret['errstr'] = 'quota lack';
            goto END;
        }

        $this->modelClassroom->startTrans();
        $this->model->startTrans();

        foreach($raw['uids'] as $v){
            $uids[] = $v;
        }
        $res = M(TUSER)->where(['uid'=>['in',$uids]])->save(['teacher_uid'=>$raw['teacher_uid']]);

        //修改新教员名额
        $res1 = $this->modelClassroom->decTeacher(
            ['column'=>'quota_left','value'=>count($raw['uids'])],
            ['uid'=>$raw['teacher_uid']])
        ;

        //修改原教员名额
        $old_teacher = [];
        foreach($user_info as $v){
            if($v['teacher_uid'])
                $old_teacher[] = $v['teacher_uid'];
        }

        $uni_tuid = [];
        foreach($old_teacher as $v){
            if(!$uni_tuid[$v])
                $uni_tuid[$v] = 0;
            $uni_tuid[$v]++;
        }

        $old_teacher = array_unique($old_teacher);

        foreach($old_teacher as $v){
            $old_teacher_info = $this->modelClassroom->incTeacher(
                ['column'=>'quota_left','value'=>$uni_tuid[$v]],
                ['uid'=>$v]);
        }


        $res = $this->model->saveClient(['uid'=>['in',$uids]],['teacher_uid'=>$raw['teacher_uid']]);



        if($res1 === false || $old_teacher_info === false){
            D('Profile')->rollback();
            D('Classroom')->rollback();
            $ret['status'] = 1;
            $ret['errstr'] = 'edit failed';
            goto END;
        }

        $this->model->commit();
        $this->modelClassroom->commit();

        $ret['status'] = E_OK;
        $ret['errstr'] = '';
END:
        $this->retReturn( $ret );
    }

    //用户邀请的成员列表
    public function userInviteList()
    {
        $raw = $this->RxData;
        $ret = [];
        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:10;
        $limit = $num*($page-1).','.$num;

        $where = [];

        $keys_up = ['up_nickname','up_name'];
        $keys = ['nickname','name','phone'];
        foreach ( $keys as $item) {
            if($raw[$item])
                $where['a.'.$item] = ['like','%'.$raw[$item].'%'];
        }

        foreach ( $keys_up as $item) {
            if($raw[$item]){
                $item0 = $item;
                $item1 = str_replace('up_','',$item0);
                $where['d.'.$item1] = ['like','%'.$raw[$item].'%'];;
            }
        }

        if($raw['up_uid'])
            $where['d.uid'] = $raw['up_uid'];
        if($raw['up_xiaozhu'])
            $where['d.xiaozhu'] = $raw['up_xiaozhu'];
        if($raw['uid'])
            $where['a.uid'] = $raw['uid'];
        if($raw['xiaozhu'])
            $where['a.xiaozhu'] = $raw['xiaozhu'];


        if($raw['time_start'] && $raw['time_end']){
            $where['a.atime'] = [
                ['egt',$raw['time_start']],
                ['lt',$raw['time_end']]
            ];
        }elseif($raw['time_start'] ) {
            $where['a.atime'] = ['egt', $raw['time_start']];
        }elseif($raw['time_end'] ){
            $where['a.atime'] = ['lt',$raw['time_end']];
        }

        $column = [
            'a.uid,a.xiaozhu,a.nickname,a.status,a.name,a.phone,a.login_time,a.atime,a.invite_n,
            d.xiaozhu as up_xiaozhu,d.uid as up_uid,d.name as up_name,d.nickname as up_nickname,d.qq as up_qq'
        ];

        $order = ['a.atime desc'];

        $result = $this->modelUser->selectUserInvite($column,$where,$limit,$order);
        if(!$result)
            goto END;

//        $count = $this->modelUser->selectUserInvite('count(*) total',$where,$limit,$order);
        $count  = $result['total'][0]['total'];

        foreach($result as &$v){
            if($v['role'])
                $v['role'] = explode(',',$v['role']);
        }
        $ret['total'] = $count;
        $ret['page_start'] = $page;
        $ret['page_n'] = count($result);
        $ret['data'] = $result['re'];

END:
        $this->retReturn( $ret );

    }



    public function qqList()
    {
        $raw = $this->RxData;
        $ret = [];

        $page = $raw['page_start']?$raw['page_start']-1:0;
        $num = $raw['page_limit']? $raw['page_limit']:10;
        $limit = $num*$page.','.$num;

        $where = [];
        if($raw['time_start'])
            $where['atime'] = ['egt',$raw['time_start']];
        if($raw['time_end'])
            $where['atime'] = ['egt',$raw['time_end']];
        if($raw['department_id'])
            $where['department_id'] = $raw['department_id'];
        if($raw['role_id'])
            $where['role_id	'] = ['like','%'.$raw['role_id'].'%'];
        if($raw['status'])
            $where['status'] = $raw['status'];

        $column = [
            'a.uid,a.status,a.name,a.qq,b.name as department,a.roles,a.roles,a.mtime'
        ];

        $order = ['a.atime desc'];

        $count  = $this->modelUser->selectQQListWithInfo($column,$where);
        $result = $this->modelUser->selectQQListWithInfo($column,$where,$limit,$order);

        $ret['total'] = count($count);
        $ret['page_start'] = $page;
        $ret['page_n'] = count($result);
        $ret['data'] = $result;

END:
        $this->retReturn( $ret );

    }

    //编辑用户资料
    public function editUser()
    {
        $raw = $this->RxData;
        $ret = [];

        $uid = $raw['uid'];
        $keys = [
            'nickname','user','pass','icon','level','name','phone','work_age',
            'city','qq','roles','email','teacher_uid','department'];
        foreach ($keys as $v){
            if($raw[$v])
                $data[$v] = $raw[$v];
        }

        if( isset( $data['pass'] ) ) $data['pass'] =  \Common\GetRealPass($data['pass']);
        //roles
        $role_name = [];
        if($raw['roles'] && count($raw['roles'])){
            $roles = $this->model->selectRoles('',['id'=>['in',$raw['roles']]]);
            foreach($roles as $v){
                $role_name[] = $v['title'];
                $role_data[] = ['uid'=>$raw['uid'],'group_id'=>$v['id']];
            }

                $role_res1 = D('AuthRule')->delAuthGroupAccess(['uid'=>$raw['uid']]);


            $data['roles'] = implode(',',$role_name);

        }
        $data['level_id'] = $data['level']?$data['level']:DEFAULT_LEVEL;

        //edit or add
        if(isset($raw['uid'])){
            $user_info = $this->model->findClient('teacher_uid',['uid'=>$raw['uid']]);
            $res = $this->model->saveClient(['uid'=>$raw['uid']],$data);

            if(empty($role_data) && !count($raw['roles'])){
                $role_res1 = D('AuthRule')->delAuthGroupAccess(['uid'=>$raw['uid']]);
                $res = $this->model->saveClient(['uid'=>$raw['uid']],['roles','']);
                $data['roles'] = '';

            }else{
                $role_res1 = D('AuthRule')->delAuthGroupAccess(['uid'=>$raw['uid']]);
                $role_res1 = D('AuthRule')->addAuthGroupAccessAll($role_data);

            }
            //修改新教员名额
            if($user_info['teacher_uid'] != $raw['teacher_uid'])
            {
                if($user_info['teacher_uid'])
                    $this->modelClassroom->incTeacher(
                        ['column'=>'quota_left','value'=>1],
                        ['uid'=>$user_info['teacher_uid']])
                    ;
                $this->modelClassroom->decTeacher(
                    ['column'=>'quota_left','value'=>1],
                    ['uid'=>$raw['teacher_uid']])
                ;
            }




            $res = $this->model->saveClient(['uid'=>$raw['uid']],$data);

            foreach ($data as $k=>$v){
                $this->out[$k] = $v;
            }
            \Common\ValidDaTokenWrite( $this->out, $raw['token'], TOKEN_COVER );

        }else{
            $data['atime'] = time();
            $exist = $this->model->findClient('',['phone'=>$raw['phone']]);
            if($exist){
                $ret['status'] = E_EXIST;
                $ret['errstr'] = 'phone already exist';
                goto END;

            }

            $data['xiaozhu'] = \Common\random( 6, 'number' );

            $res = $this->model->addClient($data);

            $uid = $res;
            if($raw['roles'] && $res){
                $roles = $this->model->selectRoles('',['id'=>['in',$raw['roles']]]);

                if(count($roles) == count($raw['roles'])){
                    $role_data = [];
                    foreach($roles as $v){
                        $role_data[] = ['uid'=>$uid,'group_id'=>$v['id']];
                    }
                }

            }
            if($role_data)
              $role_res1 = D('AuthRule')->addAuthGroupAccessAll($role_data);


            $m = M();
            $invite_code = 'c'.rand(10000, 99999).$res;
            $upuser = $m->table(TUSER)->where([ 'uid' => $res ])->save( [ 'invite_code' => $invite_code ] );

            if( empty( $d['invite_code'] ) ){
                $refer_d = [
                    'uid'           => $res,
                    'user'          => $d['user'],
                    'name'          => $d['name'],
                    'atime'         => time()
                ];
            }else{

                $str = substr( $d['invite_code'] , 0, 1 );
                $upline_id = substr( $d['invite_code'] , 6, strlen( $d['invite_code'] ) );
                $upline = $m->table( TUSER )->where([ 'uid' => $upline_id ])->find();

                if( !$upline ){
                    $res['status'] = E_INVITE;
                    $m->rollback();
                    goto END;
                }
                $refer_d = [
                    'uid'           => $res,
                    'user'          => $d['user'],
                    'name'          => $d['name'],
                    'upline'        => $upline_id,
                    'upline_user'   => $upline['user'],
                    'upline_name'   => $upline['name'],
                    'atime'         => time()
                ];
            }
            $refer_res = $m->table(TREFER)->add( $refer_d );

            //教员
            if($raw['teacher_uid'])
                $this->modelClassroom->decTeacher(
                    ['column'=>'quota_left','value'=>1],
                    ['uid'=>$raw['teacher_uid']])
                ;

        }


        $uid = $raw['uid']?$raw['uid']:$res;

        $teacher_info = D('AuthRule')->findAuthGroup('',['id'=>2]);

        //教员
        if(in_array($teacher_info['id'],$raw['roles'])){
            $exist = D('classroom')->selectTeacher('a.status', ['a.uid'=>$uid]);
            if($exist){
                if($exist[0]['status'] != 1){
                    $edit = D('classroom')->editTeacher(['status'=>1], ['uid'=>$uid]);
                }
            }else{
                $teacher_data = [
                    'uid'=>$uid,
                    'name'=>$raw['name']?$raw['name']:$raw['nickname'],
                    'atime'=>time(),
                    'quota_left'=>SYSTEM_QUOTA,
                    'quota_total'=>SYSTEM_QUOTA,
                    'status'=>1

                ];
                $add = D('classroom')->addTeacher($teacher_data);
            }

        }else{
            $exist = D('classroom')->selectTeacher('a.status', ['a.uid'=>$uid]);
            if($exist[0]['status'] == 1){
                $exist = D('classroom')->editTeacher(['status'=>0], ['uid'=>$uid]);
            }
        }


        $ret['id'] = $res;
        $ret['status'] = E_OK;
        $ret['errstr'] = '';
END:
        $this->retReturn( $ret );
    }



    public function upload(){
        $re = [];
        $head = C('PREURL');

        $field = 'picture';
        $pre_path = C("UPLOAD_PATH");
        $realpath = $pre_path.'profilr/pic_'.time().'/';

        $conf = array(
            'pre' => 'pro',
            'types' => ['jpg', 'gif', 'png', 'jpeg'],
        );

        if( !is_dir($realpath) ) $z = mkdir( $realpath, 0775, true );
        $upload_res = \Common\_Upload($field,$realpath,$conf);

        if( $upload_res['state'] != 0 ){
            $re =  $upload_res;
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




    //user detail

    public function showUserDetail(){
        $raw = $this->RxData;
        $ret = [];

        $uid = $raw['uid']?$raw['uid']:$this->out['uid'];
        $keys = ['a.*',
            'c.name as level_title,b.name as dname,d.uid as teacher_uid,d.name as teacher_name
            '];


        $res = $this->model->findUserWith($keys,['a.uid'=>$uid]);
        $up_info = $this->model->findUpline('a.uid,a.name,a.phone',['b.uid'=>$uid]);


        $rules =  D('AuthRule')->selectUserRoles('b.id,b.title',['a.uid'=>$uid]);

        $ret = $res;
        $roles = [];
        foreach($rules as $v){
//            $roles[] =['id'=>$v['id'],'title'=>$v['title']];
            $roles[] = $v['id'];
        }
        $ret['roles'] = $roles;

        $ret['teacher']['teacher_uid']  = $res['teacher_uid'];
        $ret['teacher']['teacher_name'] = $res['teacher_name'];
        unset($ret['teacher_uid']);
        unset($ret['teacher_name']);

        $ret['level']['level_id'] = $res['level_id'];
        $ret['level']['level_title'] = $res['level_title'];
        unset($ret['level_id']);
        unset($ret['level_title']);

        $ret['department']['id'] = $res['department_id'];
        $ret['department']['title'] = $res['dname'];
        unset($ret['department_id']);
        unset($ret['dname']);
        if($up_info){
            $ret['up_uid'] = $up_info['uid'];
            $ret['up_name'] = $up_info['name'];
            $ret['up_phone'] = $up_info['phone'];
        }
        $ret['icon'] = $ret['icon']?C('PREURL').$ret['icon']:'';
END:
        $this->retReturn( $ret );
    }


    public function levelList(){
        $ret = [];

        $res = D('Profile')->selectLevel('id,name');

        if( $res )
            $ret = $res;

        $this->retReturn( $ret );
    }



    //编辑用户等级
    public function editLevel()
    {
        $raw = $this->RxData;
        $ret = [];


        if(!is_array($raw['uids']))
                goto END;
        $level_info = D('Profile')->selectLevel('',['id'=>$raw['level']]);
        if(!$level_info){
            $ret['status'] = 1;
            $ret['status'] = 'level not exist';
            goto END;
        }

        $user_info = D('Profile')->selectClient('',['a.uid'=>['in',$raw['uids']]]);
        if(count($user_info) < count($raw['uids'])){
            $ret['status'] = 1;
            $ret['status'] = 'uid not exist';
            goto END;
        }



        D('Profile')->startTrans();

        foreach($raw['uids'] as $v){
            $uids[] = $v;
        }
        $res = M(TUSER)->where(['uid'=>['in',$uids]])->save(['level_id'=>$raw['level']]);


        if( $res === false){
            D('Profile')->rollback();
            D('Classroom')->rollback();
            $ret['status'] = 1;
            $ret['status'] = 'edit failed';
            goto END;
        }

        D('Profile')->commit();

        $ret['status'] = E_OK;
        $ret['errstr'] = '';
END:
        $this->retReturn( $ret );
    }



    //roles

    public function roleList(){
        $ret = [ 'total' => 0 , 'page_start' => 0, 'page_n' => 0, 'data' => [] ];
        $raw = $this->RxData;

        $page_start = ( !isset( $raw[ 'page_start' ] ) || !is_numeric( $raw[ 'page_start' ] ) )?'1':$raw[ 'page_start' ];
        $page_limit = ( !isset( $raw[ 'page_limit' ] ) || !is_numeric( $raw[ 'page_limit' ] ) )?10:$raw[ 'page_limit' ];
        $limit = ($page_start-1)*$page_limit.','.$page_limit;
        $ret['page_start'] = $page_start;

        $where['id'] = array('neq','1');

        if( isset( $raw['title'] ) && !empty( $raw['title'] ) )
            $where['title'] = [ 'like', '%'.$raw['title'].'%' ];

        if( isset( $raw['status'] ) && !empty( $raw['title'] ) && in_array( $raw['status'], [0,1] ) )
            $where['status'] = $raw['status'];

        $total = $this->m_m1->selectAuthGroup( 'count(id) num', $where );
        $res = $this->getAuthGroup( $where, $limit );
        foreach ($res as $val) {
            $ret['data'][] = array(
                'id'    => $val['id'],
                'status'=> $val['status'],
                'title' => $val['title'],
                'rem'   => $val['rem']
            );
        }

        if( $total )
            $ret['total'] = $total[0]['num'];

        $ret['page_n'] = count($ret['data']);
        END:
        $this->retReturn($ret);
    }

    public function ShowAuthGroupDetail(){
        $ret = [];
        $raw = $this->RxData;

        if( isset( $raw['id'] ) && is_numeric( $raw['id'] ) ){
            $where['id'] = $raw['id'];
            $group = $this->getAuthGroup( $where );
            foreach ($group as $val) {
                $ret  = array(
                    'id'    => $val['id'],
                    'title' => $val['title'],
                    'status'=> $val['status'],
                    'rem'   => $val['rem'],
                    'rule'  => explode(',',$val['rule']),
                );
            }
        }

        $this->retReturn($ret);
    }

    public function ShowAuthRule(){
        $ret = [];

        $where = [];
        $res = $this->getAuthRule($where);

        foreach ($res as $key => $value) {
            $class = $value['class'];
            $category= $value['category'];
            if($category=='title'){
                $tip[ $value['order'] ] = [ 'title' => $value['title'], 'id' => $value['id'] ];
                $order[ $class ] = $value['order'];
            }
        }

        foreach ($res as $key => $value) {
            $class = $value['class'];
            $category= $value['category'];
            if($category!='title'){
                $num = $order[$class];
                $cache[$category][$num][] = array(
                    'id'    => $value['id'],
                    'title' => $value['title'],
                );
            }
        }

        foreach ($tip as $key => $value) {
            // $ret['api'][] = [
            //     "id"        => $value['id'],
            //     "title"     => $value['title'],
            //     "data"      => $cache['api'][$key]?$cache['api'][$key]:[]
            // ];

            $ret[] = [
                "id"        => $value['id'],
                "title"     => $value['title'],
                "data"      => $cache['page'][$key]?$cache['page'][$key]:[ 'title' => $value['title'], 'id' => $value['id'] ]
            ];
        }

        $this->retReturn($ret);
    }

    public function editAuthGroup(){
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $raw = $this->RxData;

        $keys = [ 'title', 'rules', 'status', 'rem' ];
        foreach ($keys as $val) {
            if( isset( $raw[ $val ] ) && $raw[ $val ] !== '' )
                $d[$val] = $raw[ $val ];
        }
        $id = ( isset( $raw['id'] ) && is_numeric( $raw['id'] ) )?$raw['id']:'0';

        if( !$d ) goto END;
        if( $id ){
            if( $id == 1 ){
                $ret['status'] = E_AC;
                goto END;
            }

            $res = $this->m_m1->saveAuthGroup( [ 'id' => $id ], $d );

            if( $res === false )
                goto END;
        }else{
            if( $rules || $title ) goto END;

            $res = $this->m_m1->addAuthGroup( $d );

            if( !$res )
                goto END;

            $ret['id'] = $res;
        }

        $ret['status'] = E_OK;
        END:
        $this->retReturn($ret);
    }



    public function getAuthGroup($where='', $limit=''){              //内部方法，获取角色分组信息

        $group = $this->m_m1->selectAuthGroup( '', $where, '', $limit );
        foreach ($group as $key => $value) {
            $data[] = array(
                'id'    => $value['id'],
                'title' => $value['title'],
                'status'=> $value['status'],
                'rem'   => $value['rem'],
                'rule'  => $value['rules'],
            );
        }

        return $data;
    }

    public function getAuthRule($where=''){               //内部方法，获取所有权限节点信息

        $order = ['`order` asc'];
        $rule = $this->m_m1->selectAuthRule( '', $where, $order );

        foreach ($rule as $key => $value) {
            $data[] = array(
                'id'    => $value['id'],
                'name'  => $value['name'],
                'title' => $value['title'],
                'status' => $value['status'],
                'category' => $value['category'],
                'class' => $value['class'],
                'order' => $value['order'],
            );
        }

        return $data;
    }


    //预设问题



    public function questionList(){
        $ret = [];


        $res = $this->modelUser->selectQuestion( 'id,title as name,status' );
        if( $res )
            $ret = $res;

END:
        $this->retReturn( $ret );
    }

    public function editQuestion()
    {
        $raw = $this->RxData;
        $ret['status'] = E_OK;
        $ret['errstr'] = '';

        $keys = [ 'title', 'status' ];
        foreach ($keys as $val) {
            if( isset( $raw[ $val ] ) && $raw[ $val ] !== '' )
                $data[$val] = $raw[ $val ];
        }

        if(empty($raw['title'])){
            $ret['status'] = E_SYSTEM;
            goto END;

        }
        $res = $this->modelUser->addQuestion($data);

        if(!$res){
            $ret['status'] = E_SYSTEM;
            goto END;

        }

        END:
        $this->retReturn( $ret );
    }

}
