<?php
namespace Admin\Controller;

class UserController extends GlobalController
{
    protected $model;
    protected $m_m;
    protected $m_m1;
    protected $modelUser;
    protected $modelSystem;
    protected $modelClassroom;

    public function _initialize($check = false)
    {

        parent::_initialize($check = false);
        $this->model = D('Admin/Profile');
        $this->modelSystem      = D('Admin/System');
        $this->modelClassroom   = D('Admin/Classroom');
        $this->modelUser = D('Admin/User');
        $this->m_m  = D('Admin/Manage');
        $this->m_m1 = D('Admin/AuthRule');
    }

    public function userList()
    {
        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];


        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:10;
        $limit = $num*($page-1).','.$num;
        $where = [];
        $where['a.uid'] = ['neq',$this->out['uid']];

        $keys = ['phone','name','nickname','status_stu','status_te'];
        foreach ( $keys as $item) {
            if($raw[$item])
                $where['a.'.$item] = ['like','%'.$raw[$item].'%'];
        }
        if($raw['teacher_name']){
            $t_info = D('Client/Profile')->findClient('uid',['nickname'=>['like','%'.$raw['teacher_name'].'%']]);

            if($t_info)
                $where['a.teacher_uid'] = $t_info['uid'];
        }
        if($raw['role_id']){
            $t_info = $this->modelSystem->findRoleGroup('name',['id'=>$raw['role_id']]);
            if($t_info)
                $where['a.roles'] = ['like','%'.$t_info['name'].'%'];
        }

        if($raw['level_id'])
            $where['a.level_id'] = $raw['level_id'];

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
            a.invite_n,a.qq,a.roles as role,a.status_te,a.status_stu,
            c.name as level_name,e.nickname as teacher_name'
        ];

        $order = ['a.atime desc'];

        $result = $this->modelUser->selectUser($column,$where,$limit,$order);

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


    //用户邀请的成员列表
    public function userInviteList()
    {
        $raw = $this->RxData;
        $ret = [];
        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:10;
        $limit = $num*($page-1).','.$num;

        $where = [];

        $keys_up = ['up_nickname','up_name','up_xiaozhu'];
        $keys = ['nickname','name','phone','xiaozhu'];
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

        if($raw['uid'])
            $where['a.uid'] = $raw['uid'];



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

        $keys = [
            'nickname','user','pass','icon','level','name','phone','work_age',
            'city','qq','roles','email','teacher_uid','department'
        ];


        if(!$raw['uid']){
            $keys_m = ['pass','name','phone'];
            foreach($keys_m as $v) {
                if (!isset($raw[$v]) || empty($raw[$v])) {
                    $ret['status'] = E_DATA;
                    $ret['errstr'] = 'wrong data!';
                    goto END;
                }
            }
            if(!$raw['qq'])
                $data['qq'] = '';
        }

        foreach ($keys as $v){
            if($raw[$v])
                $data[$v] = $raw[$v];
        }


        if( isset( $data['pass'] ) ) $data['pass'] =  \Common\GetRealPass($data['pass']);

        $data['level_id'] = $data['level']?$data['level']:DEFAULT_LEVEL;

//roles
        $role_name = [];
        $raw['roles_group'] = $raw['roles_group']?$raw['roles_group']:[DEFAULT_ROLE];

        if(count($raw['roles_group']) >= 2){
            foreach($raw['roles_group'] as $k=>$v1){
                if($v1 == ROLE_DB_NOR)
                    unset($raw['roles_group'][$k]);
            }
        }
//默认为教员添加学员角色
//        if(in_array(ROLE_DB_TEA,$raw['roles_group']) && !in_array(ROLE_DB_STU,$raw['roles_group']))
//            $raw['roles_group'][] = ROLE_DB_STU;


        $roles = $this->modelSystem->selectRoleGroup('',['id'=>['in',$raw['roles_group']]]);
        foreach($roles as $v){
            $role_name[] = $v['name'];
        }

        if(in_array(ROLE_DB_TEA,$raw['roles_group']))
            $data['status_te'] = SYS_OK;
        if(in_array(ROLE_DB_STU,$raw['roles_group']))
            $data['status_stu'] = SYS_OK;
        $data['roles'] = implode(',',$role_name);


        if(isset($raw['uid'])){
            $user_info = $this->model->findClient('',['uid'=>$raw['uid']]);
            if($raw['teacher_uid'] && $user_info['teacher_uid'] != $raw['teacher_uid'])
                $data['stime'] = time();

            $res = $this->model->saveClient(['uid'=>$raw['uid']],$data);
            if($data['phone'])  unset($data['phone']);
            $uid = $raw['uid'];

            $res = $this->model->saveClient(['uid'=>$raw['uid']],$data);

//修改新教员名额
            if($raw['teacher_uid'] && $user_info['teacher_uid'] != $raw['teacher_uid']){
                if($user_info['teacher_uid'])
                    $this->modelClassroom->incTeacher(
                        ['column'=>'quota_left','value'=>1],
                        ['uid'=>$user_info['teacher_uid']])
                    ;
                $this->modelClassroom->decTeacher(
                    ['column'=>'quota_left','value'=>1],
                    ['uid'=>$raw['teacher_uid']])
                ;
//添加解绑教员记录
                $data_t = [
                    [
                        'teacher_uid'=>$user_info['teacher_uid'],
                        'new_teacher_uid'=>$raw['teacher_uid'],
                        'uid'=>$raw['uid'],
                        'atime'=>time(),
                        'type'=>TIE,
                        'mtime'=>time(),
                        'check'=>PASS,
                    ],
                ];
                $res_tie = D('Admin/Membership')->addRecordChgTeaAll($data_t);
            }

            if(in_array(ROLE_DB_TEA,$raw['roles_group'])){
                if(isset($raw['quota_left']))
                    $res2 = D('classroom')->editTeacher(['quota_left'=>$raw['quota_left']], ['uid'=>$raw['uid']]);
            }

        }else{
            $data['atime'] = time();
            $exist = $this->model->findClient('',['phone'=>$raw['phone']]);
            if($exist){
                $ret['status'] = E_EXIST;
                $ret['errstr'] = 'phone already exist';
                goto END;

            }

            $data['xiaozhu'] = \Common\random( 6, 'number' );
            if($raw['teacher_uid'])
                $data['stime'] = time();

            $uid = $this->model->addClient($data);

            $m = M();
            $invite_code = 'c'.rand(10000, 99999).$uid;
            $upuser = $m->table(TUSER)->where([ 'uid' => $uid ])->save( [ 'invite_code' => $invite_code ] );

            if( empty( $d['invite_code'] ) ){
                $refer_d = [
                    'uid'           => $uid,
                    'user'          => $d['user'],
                    'name'          => $d['name'],
                    'atime'         => time()
                ];
            }else{

                $str = substr( $d['invite_code'] , 0, 1 );
                $upline_id = substr( $d['invite_code'] , 6, strlen( $d['invite_code'] ) );
                $upline = $m->table( TUSER )->where([ 'uid' => $upline_id ])->find();

                if( !$upline ){
                    $ret['status'] = E_INVITE;
                    $m->rollback();
                    goto END;
                }
                $refer_d = [
                    'uid'           => $uid,
                    'user'          => $d['user'],
                    'name'          => $d['name'],
                    'upline'        => $upline_id,
                    'upline_user'   => $upline['user'],
                    'upline_name'   => $upline['name'],
                    'atime'         => time()
                ];
            }
            $refer_res = $m->table(TREFER)->add( $refer_d );

//教员名额
            if($raw['teacher_uid']){
                $this->modelClassroom->decTeacher(
                    ['column'=>'quota_left','value'=>1],
                    ['uid'=>$raw['teacher_uid']])
                ;
//添加绑定教员记录
                $data_t = [

                    [
                        'teacher_uid'=>'',
                        'new_teacher_uid'=>$raw['teacher_uid'],
                        'uid'  =>$uid,
                        'atime'=>time(),
                        'type' =>TIE,
                        'mtime'=>time(),
                        'check'=>PASS,
                    ]
                ];
                $res_tie = D('Admin/Membership')->addRecordChgTeaAll($data_t);
            }
        }

//access data
        if($raw['roles']){
            $roles_access = $this->model->selectRoles('',['id'=>['in',$raw['roles']]]);
            foreach($roles_access as $v){
                $role_access_data[] = ['uid'=>$uid,'group_id'=>$v['id']];
            }
        }

//roles data
        foreach($roles as $v){
            $role_name[] = $v['name'];
            $role_group_data[] = ['uid'=>$uid,'group_id'=>$v['id'],'status'=>SYS_OK];
        }

        if(in_array(ROLE_DB_TEA,$raw['roles_group']) || in_array(ROLE_DB_STU,$raw['roles_group'])){
            $role_group_data[] = ['uid'=>$uid,'group_id'=>ROLE_DB_NOR,'status'=>SYS_FORBID];
        }else{
            unset($role_group_data);
            $role_group_data[] = ['uid'=>$uid,'group_id'=>ROLE_DB_NOR,'status'=>SYS_OK];
        }


//add access
        $role_res1 = D('AuthRule')->delAuthGroupAccess(['uid'=>$uid]);
        if( !empty($role_access_data) ){
            $role_res1 = D('AuthRule')->addAuthGroupAccessAll($role_access_data);
        }

//add roles
        $role_res2 = $this->modelSystem->delRoleGroupUser(['uid'=>$uid]);
        if(!empty($role_group_data)){
            $role_res2 =$this->modelSystem->addRoleGroupUserAll($role_group_data);
        }


//角色为教员
        $exist_teacher = D('Client/Profile')->findTeacher('', ['uid'=>$uid]);
        if(in_array(ROLE_DB_TEA,$raw['roles_group'])){
            if($exist_teacher){
                if($exist_teacher['status'] == SYS_FORBID){
                    $edit = D('classroom')->editTeacher(['status'=>SYS_OK], ['id'=>$exist_teacher['id']]);
                }
            }else{
                $teacher_data = [
                    'uid'=>$uid,
                    'name'=>$raw['name']?$raw['name']:$raw['nickname'],
                    'atime'=>time(),
                    'quota_left'=>C('TEACHER_QUOTA'),
                    'quota_total'=>C('TEACHER_QUOTA'),
                    'status'=>1

                ];
                $add = D('classroom')->addTeacher($teacher_data);
            }

        }else{
            if($exist_teacher && $exist_teacher['status'] == SYS_OK){
                $edit_t = D('classroom')->editTeacher(['status'=>SYS_FORBID], ['id'=>$exist_teacher['id']]);
            }
        }


        $ret['id'] = $uid;
        $ret['status'] = E_OK;
        $ret['errstr'] = '';
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
        $teacher_info = D('Classroom')->selectTeacher('',['a.uid'=>$raw['teacher_uid'],'a.status'=>1]);
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
        $res = M(TUSER)->where(['uid'=>['in',$uids]])->save(['teacher_uid'=>$raw['teacher_uid'],'stime'=>time()]);

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

        //修改新教员名额

        $count_new = D('Client/Profile')->selectClient('count(*) total',['teacher_uid'=>$raw['teacher_uid']]);

        $quota_left = C('TEACHER_QUOTA')-$count_new[0]['total'];
        $res1 = D('Client/Profile')->saveTeacher(['quota_left'=>$quota_left],['uid'=>$raw['teacher_uid']]);

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
        $uid = $raw['uid'];
        if($uid == $this->out['uid'])
            goto END;
        $keys = ['a.*',
            'c.name as level_title,b.name as dname,d.uid as teacher_uid,d.name as teacher_name
            '];


        $res = $this->model->findUserWith($keys,['a.uid'=>$uid]);
        $up_info = $this->model->findUpline('a.uid,a.name,a.phone',['b.uid'=>$uid]);
        $ret = $res;

        //access
        $rules =  D('AuthRule')->selectUserRoles('b.id,b.title',['a.uid'=>$uid]);
        $roles = [];
        if($rules){
            foreach($rules as $v){
                $roles[] = $v['id'];
            }
        }

        $ret['roles'] = $roles?$roles:'';

        //roles
        $roles_info =  $this->modelSystem->selectUserRoleGroup('b.id,b.name',['a.uid'=>$uid,'a.status'=>SYS_OK]);
        $roles_group = [];
        $ret['admin'] = ROLE_DB_NOR;
        if($roles_info){
            foreach($roles_info as $v){
                $roles_group[] = $v['id'];
            }
            if(in_array(ROLE_DB_TEA,$roles_group)){
                $ret['admin'] = ROLE_DB_TEA;
                $t_info =  D('Client/Profile')->findTeacher('',['uid'=>$raw['uid']]);
                $ret['quota_left'] = (int)$t_info['quota_left'];
            }elseif(in_array(ROLE_DB_STU,$roles_group)){
                $ret['admin'] = ROLE_DB_STU;
            }else{
                $ret['admin'] = ROLE_DB_NOR;
            }
        }

        $teacher_info = $this->model->findClient('level_id',['uid'=>$res['teacher_uid']]);
        $teacher_level_top= D('Admin/Classroom')->selectTeacher('max(level_id) as level_id',['a.status'=>SYS_OK,'a.quota_left'=>['gt',0]]);

        $ret['level_top'] = $teacher_level_top[0]['level_id'];

        $level = \Common\getLevelInfo();
        $ret['roles_group'] = $roles_group?$roles_group:[];
        $ret['teacher']['teacher_uid']  = $res['teacher_uid'];
        $ret['teacher']['teacher_name'] = $res['teacher_name'];
        $ret['teacher']['level_id']     = $teacher_info['level_id'];
        $ret['teacher']['level']        = $level[$teacher_info['level_id']]['name'];
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

        $res = D('Profile')->selectLevel('id,name,extend',['status'=>['neq',SYS_FORBID]]);

        if( $res )
            $ret = $res;

        $this->retReturn( $ret );
    }



    //编辑用户等级
    public function editLevel(){
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



    //access

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

        if( isset( $raw['status'] ) && in_array( $raw['status'], [0,1] ) )
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

        if(!$raw['rules']){
            $ret = [ 'status' => E_DATA, 'errstr' => '' ];
            goto END;
        }
        $keys = [ 'title', 'status', 'rem' ,'rules'];
        foreach ($keys as $val) {
            if( isset( $raw[ $val ] )  )
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

    public function editQuestion(){
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
