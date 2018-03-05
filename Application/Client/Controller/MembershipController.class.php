<?php

namespace Client\Controller;

class MembershipController extends GlobalController
{

    protected $model;
    protected $modelUser;
    protected $modelCheck;
    protected $modelSystem;
    protected $modelProfile;


    public function _initialize($check = true)
    {
        parent::_initialize($check);
        $this->model        = D('Client/Membership');
        $this->modelProfile = D('Client/Profile');
        $this->modelSystem  = D('Admin/System');
        $this->modelCheck   = D('Client/Check');
        $this->modelUser    = D('Client/User');
    }

    public function teacherList()
    {
        $raw = $this->RxData;
        $ret = [];

        $page = $raw['page_start'] ? $raw['page_start'] : 1;

        $num  = $raw['page_limit'] ? $raw['page_limit'] : C('PAGE_LIMIT');
        $limit = $num * ($page-1).','.$num;

        $where = [];

        $where['a.status'] = SYS_OK;
        $level_id= $this->out['level_id'];

        if(isset($raw['uid']) && is_numeric($raw['uid'])){
            $user_info = $this->modelProfile->findClient('uid,level_id',['uid'=>$raw['uid']]);
            if($user_info)
                $level_id= $user_info['level_id'];

        }
        if($raw['level_id'])
            $level_id= $raw['level_id'];


        $where['b.level_id'] = ['gt',$level_id];

        if($this->out['teacher_uid']){
            $where['b.uid'][] = ['neq',$this->out['uid']];
            $where['b.uid'][] = ['neq',$this->out['teacher_uid']];
            $where['b.uid'][] = 'or';
        }else{
            $where['b.uid'] = ['neq',$this->out['uid']];
        }

        $level_limit = D('Admin/System')->selectLevel('max(id) as id');
        $result = $this->selectNewTeacher($level_id,$limit,$level_limit[0]['id']);

        if(!$result){
            $result = $this->selectNewTeacher(1,$limit,$level_limit[0]['id']);
        }

//等级
        $level_info = $this->modelProfile->selectLevel('id,name');
        $level = [];
        foreach($level_info as $v)
            $level[$v['id']] = $v['name'];


        foreach ($result as $k=>&$item) {
            if($this->out['uid'] == $item['uid'] || $this->out['teacher_uid'] == $item['uid'])
                unset($result[$k]);
            $item['level'] = $level[$item['level_id']];
            $item['icon'] = \Common\GetCompleteUrl($item['icon']);
        }
        $result1 = [];

        foreach ($result as $v1) {
            $result1[] = $v1;
        }
        $ret['page_start'] = $page;
        $ret['total']  = count($result);
        $ret['page_n'] = count($result);
        $ret['data']   = $result1;

END:
        $this->retReturn($ret);

    }


    /*
     * 获取教员列表
     */

    protected function selectNewTeacher($level,$limit,$level_limit){
        $level++;
        $teacher_info = $this->model->selectTeacher(
            'b.uid,b.name,b.nickname,b.icon,a.quota_left,b.level_id',
            ['b.level_id'=>$level,'b.status_te'=>SYS_OK,'a.status'=>SYS_OK,'a.quota_left'=>['gt',1]],
            $limit,
            'a.quota_left desc'
        );

        if(!$teacher_info && $level<=$level_limit){
            $this->selectNewTeacher($level,$limit,$level_limit);
        }
        return $teacher_info;

    }

    /**
     * 普通成员申请学员
     */

    public function applyStu()
    {
        $raw = $this->RxData;
        $ret = [ 'status' => E_OK, 'errstr' => '' ];
        $t = time();

//判断角色
        $roles_tea = [
            ROLE_BIN_STU,
            ROLE_BIN_NOR+ROLE_BIN_STU,
            ROLE_BIN_TEA+ROLE_BIN_STU,
            ROLE_BIN_NOR+ROLE_BIN_STU+ROLE_BIN_TEA
        ];

        if(in_array($this->out['admin'],$roles_tea)){
            $ret['status'] = E_STATUS;
            $ret['errstr'] = 'no need for apply';
            goto END;
        }

        //判断状态

        if($raw['id']){
            $exist = $this->model->findRecord('', ['id'=>$raw['id']]);
            if($exist['check'] != LINE){
                $ret['status'] = E_EXIST;
                $ret['errstr']  = 'judged already';
                goto END;
            }elseif($exist['check'] == PASS && in_array($this->out['admin'],$roles_tea) ){
                $ret['status'] = E_STATUS;
                $ret['errstr']  = 'no need for apply';
                goto END;
            }
        }else{
            $exist = $this->model->findRecord('', ['uid'=>$this->out['uid'],'check'=>LINE]);
            if($exist){
                $ret['status'] = E_EXIST;
                $ret['errstr']  = 'waitting please';
                goto END;
            }
        }


        $keys_m = ['sec_company','sec_account','sec_name','sec_img'];
        $keys = ['sec_company','sec_account','sec_name','teacher_uid'];
        foreach($keys_m as $v){
            if(!isset($raw[$v]) || $raw[$v] == null){
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
        }
        $data = [];
        foreach($keys as $v){
            if(isset($raw[$v])){
                $data[$v] = $raw[$v];
            }
        }
        $data['sec_img'] = serialize($raw['sec_img']);


//教员信息
        if(empty($raw['teacher_uid'])){
            $teacher_info = $this->model->findTeacher('b.uid',['b.level_id'=>2,'a.status'=>1],'','a.quota_left desc');
            $data['teacher_uid'] = $teacher_info['uid'];
        }

        if($raw['id'] ) {
            $exist = $this->model->findRecord('', ['id' => $raw['id']]);
            if(!$exist){
                $ret['status'] = E_NOEXIST;
                $ret['errstr'] = 'not exists';
                goto END;
            }

            if($exist['check'] == REJECT){
                $ret['status'] = E_AC;
                $ret['errstr'] = 'can not change a stable data';
                goto END;
            }

        }else{
            $role_data = $this->modelSystem->findUserRole('',
                ['uid'=>$this->out['uid'],'group_id'=>ROLE_STUDENT]
            );

            if(!$role_data){
//添加角色
                $role_add = $this->modelSystem->addUserRole(
                    [
                        'uid'       =>  $this->out['uid'],
                        'group_id'  =>  ROLE_STUDENT,
                        'status'    =>  SYS_FORBID
                    ]
                );
            }
        }


        $roles = $this->modelSystem->selectUserRoleGroup(
            'b.name',
            [
                'a.uid'=>$this->out['uid'],
                'group_id'=>['neq',ROLE_DB_NOR]
            ]
        );

        $roles_name = [];
        foreach($roles as $v){
            $roles_name[] = $v['name'];
        }

        $user_roles = implode(',',$roles_name);

        $data['level_id'] = $this->out['level_id'];
        $data['atime']  = $t;
        $data['uid']    = $this->out['uid'];

        $this->model->startTrans();

        if($raw['id'] && is_numeric($raw['id'])){

            $modify = MODIFY;
            $id = $raw['id'];

//更改原审核数据状态
            $res = $this->model->editRecord($data, ['id' => $raw['id']]);

            if(!$res ){
                $this->model->rollback();
                $ret['status'] = E_SYSTEM;
                $ret['errstr'] = 'edit failed';
                goto END;
            }

            $role_data = $this->modelSystem->findUserRole('',
                ['uid'=>$this->out['uid'],'group_id'=>ROLE_STUDENT]
            );

        }else{
            $modify = ADD;
            $res = $this->model->addRecord($data);
            $ret['id'] = $id = $res;
            if( !$res ){
                $this->model->rollback();
                $ret['status'] = E_STATUS;
                $ret['errstr'] = 'add failed';
                goto END;
            }
        }


//新教员名额-1
        $res_new = $this->modelProfile->decTeacher(
            ['column'=>'quota_left','value'=>1],
            ['uid'=>$data['teacher_uid']])
        ;
//添加绑定教员记录
        $data_t = [
            'new_teacher_uid'=>$data['teacher_uid'],
            'uid'=>$this->out['uid'],
            'atime'=>time(),
            'type'=>TIE,
        ];
        $res_tie = $this->model->addRecordChgTea($data_t);

//审核数据
        $check['data'] = [
            'uid'    => $this->out['uid'],
            'modify' => $modify,
            'type'   => C_APP_STU,
            'atime'  => time(),
            'desc'   => '',
        ];
//审核拒绝 恢复当前状态
        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'table' => TUSER,
            'tid'   => C_BACK_STATUS,
            'update'   => 1,
            'data'  => serialize(['status_stu'=>$this->out['status_stu']])
        ];
        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid'],'group_id'=>ROLE_DB_STU]),
            'table' => TROLE_USER,
            'tid'   => 0,
            'update' => 2,
            'data'  => serialize(['status'=>SYS_OK]),
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid'],'group_id'=>ROLE_DB_NOR]),
            'table' => TROLE_USER,
            'tid'   => 0,
            'update' => 2,
            'data'  => serialize(['status'=>SYS_FORBID]),
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['id'=>$id]),
            'table' => TUSER_APPLY_REC,
            'tid'   => 0,
            'update' => 1,
            'data'  => '',
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['id'=>$res_tie]),
            'table' => TUSER_CHGTEA_REC,
            'tid'   => 0,
            'update' => 1,
            'data'  => '',
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'table' => TUSER,
            'tid'   => 0,
            'update' => 2,
            'data'  => serialize(['teacher_uid'=>$data['teacher_uid'],'roles'=>$user_roles,'status_stu'=>SYS_OK]),
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'table' => TUSER,
            'tid'   => 3,
            'update' => 2,
            'data'  => '',
        ];

        if($data['teacher_uid'])
            $check['data_ext'][] = [
                'condition'   => serialize(['uid'=>$data['teacher_uid']]),
                'table' => TUSER_T,
                'tid'   => 2,
                'update' => 2,
                'data'  => '',
            ];

//添加审核数据
        $check_id = A('Check')->addCheck($check, 1);
        $data1['check_id'] = $check_id;
        $data1['mtime'] = null;
        $data1['check'] = LINE;
        $res_record = $this->model->editRecord($data1, ['id' => $id]);
        $res_recored_chg_tea = $this->model->editRecordChgTea($data1, ['id' => $res_tie]);


        $res_chg_status = $this->modelProfile->saveClient(
            ['uid'=>$this->out['uid']],
            [
                'status_stu' =>CHG_LINE
            ]
        );

        if(!$res_record || !$res_recored_chg_tea || !$check_id || !$res_chg_status){
            $this->model->rollback();
            $ret['status'] = E_SYSTEM;
            $ret['errstr'] = 'edit failed';
            goto END;
        }


        if($raw['sec_img']){
            $temp =[];
            foreach($raw['sec_img'] as $v){
                $temp[] = $v['path'];
            }
            $d['img_info'] = implode(',',$temp);

            $edit_img = $this->model->editAttach(['pid'=>$id],['path'=>['in',$temp]]);
        }

        $this->model->commit();

END:
        $this->retReturn($ret);

    }

    /**
     * 申请学员记录列表
     */

    public function showApplyStuList(){

        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:C('PAGE_LIMIT');
        $limit = $num*($page-1).','.$num;


        if($raw['level_id'])
            $where['a.level_id']= $raw['level_id'];

        $where['uid']= $this->out['uid'];


        $res = $this->model->selectRecord(
            '',
            $where,
            $limit,
            'id desc'
        );

        if(!$res)
            goto END;

//用户信息
        $uids = [];
        foreach($res as $v){
            $uids[] = $v['teacher_uid'];
        }
        $user_info = $this->modelProfile->selectClient('icon,level_id,uid,nickname,name',['uid'=>['in',$uids]]);
        $user = [];
        if($user_info)
            foreach ($user_info as $val) {
                $user[$val['uid']] = [
                    'uid'   =>  $val['uid'],
                    'name'  =>  $val['name'],
                    'nickname'  =>  $val['nickname'],
                    'icon'   =>  \Common\GetCompleteUrl($val['icon'])
                ];
            }


        $total = $this->model->selectRecord('count(*) total',$where);

        foreach($res as &$v){
            if($v['sec_img'])
                $v['sec_img'] =unserialize($v['sec_img']);
            $v['teacher_name'] = $user[$v['teacher_uid']]['nickname'];
        }

        $ret['total']  = $total[0]['total'];
        $ret['page_n'] = count($res);
        $ret['page_start'] = $page;
        $ret['data'] = $res;

END:

        $this->retReturn($ret);

    }

    /**
     * 设置申请状态
     */

    public function setStatus(){
        $ret = [ 'status' => E_OK, 'errstr' => '' ];
        $raw = $this->RxData;

        $keys_m = ['id','type','status'];
        foreach ($keys_m as $val) {
            if (!isset($raw[$val]) || $raw[$val] === '') {
                $ret['status'] = E_DATA;
                goto END;
            }
        }
        if( $raw['status'] != 2 ) goto END;

        switch($raw['type']){
            case 1:
                $fun = 'findRecord';
                $fun1 = 'editRecord';
                break;
            case 2:
                $fun = 'findRecordChgTea';
                $fun1 = 'editRecordChgTea';
                break;
            case 3:
                $fun = 'findRecordTea';
                $fun1 = 'editRecordTea';
                break;
            case 4:
                $fun = 'findRecordUpgrade';
                $fun1 = 'editRecordUpgrade';
                break;
            default:
                goto END;
                break;

        }

        $exist = $this->model->$fun('', ['id' => $raw['id']]);
        if(!$exist){
            $ret['status'] = E_NOEXIST;
            $ret['errstr'] = 'not exists';
            goto END;
        }
        if($exist['check'] != LINE){
            $ret['status'] = E_STATUS;
            $ret['errstr'] = 'already checked';
            goto END;
        }
        if($exist['submit'] != HWK_SUB){
            $ret['status'] = E_EXIST;
            $ret['errstr'] = 'already canceled1';
            goto END;
        }


        $res_check = D('Client/Check')->editCheck(
            ['status'=>4],
            ['id' => $exist['id']]
        );

        $res = $this->model->$fun1(['submit'=>2,'check'=>4], ['id' => $raw['id']]);

        if(!$res){
            $this->model->rollback();
            $ret['status'] = E_SYSTEM;
            $ret['errstr'] = 'edit failed';
            goto END;
        }

END:
        $this->retReturn( $ret );
    }



    /**
     *申请学员记录详情
     */
    public function showApplyStuDetail()
    {
        $ret = [];

        $raw = $this->RxData;

        if(!$raw['id'])
            goto END;

        $exist = $this->model->findRecord('id,check_id', ['id'=>$raw['id']]);
        if($exist['check'] == LINE){
            $ret['status'] = E_NOEXIST;
            $ret['errstr']  = 'not exist';
            goto END;
        }
        $where['uid'] = $this->out['uid'];
        $where['id'] = $raw['id'];

        $result = $this->model->findRecord('',$where);

        if(!$result)
            goto END;

        if($result['sec_img'])
            $result['sec_img'] = unserialize($result['sec_img']);

        $ret = $result;


END:
        $this->retReturn($ret);
    }

    /**
     * 申请成为教员
     */

    public function applyTeacher(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_OK, 'errstr' => '' ];
        $t = time();
//判断星级
        if($this->out['level_id'] == DEFAULT_LEVEL){
            $ret['status'] = E_SYSTEM;
            $ret['errstr']  = 'u should get a higher level first';
            goto END;
        }
//判断角色
        $roles_tea = [
            ROLE_BIN_TEA,
            ROLE_BIN_NOR+ROLE_BIN_TEA,
            ROLE_BIN_TEA+ROLE_BIN_STU,
            ROLE_BIN_NOR+ROLE_BIN_STU+ROLE_BIN_TEA
        ];

        if(in_array($this->out['admin'],$roles_tea)){
            $ret['status'] = E_STATUS;
            $ret['errstr'] = 'no need for apply';
            goto END;
        }

//判断状态
        if($raw['id']){
            $exist = $this->model->findRecordTea('', ['id'=>$raw['id']]);
            if($exist['check'] == LINE){
                $ret['status'] = E_EXIST;
                $ret['errstr']  = 'judging';
                goto END;
            }elseif($exist['check'] == PASS){
                $ret['status'] = E_STATUS;
                $ret['errstr']  = 'no need for apply';
                goto END;
            }

        }else{
            $exist = $this->model->findRecordTea('id,check_id,check', ['uid'=>$this->out['uid'],'check'=>LINE]);

            if($exist){
                $ret['status'] = E_EXIST;
                $ret['errstr']  = 'waitting please';
                goto END;
            }

            $role_data = $this->modelSystem->findUserRole('',
                ['uid'=>$this->out['uid'],'group_id'=>ROLE_DB_TEA]
            );

            if(!$role_data){
//添加角色
                $role_add = $this->modelSystem->addUserRole(
                    [
                        'uid'       =>  $this->out['uid'],
                        'group_id'  =>  ROLE_DB_TEA,
                        'status'    =>  SYS_FORBID
                    ]
                );

            }
        }

        $roles = $this->modelSystem->selectUserRoleGroup(
            'b.name',
            [
                'a.uid'=>$this->out['uid'],
                'group_id'=>['neq',ROLE_DB_NOR]
            ]
        );
        $roles_name = [];
        foreach($roles as $v){
            $roles_name[] = $v['name'];
        }

        $user_roles = implode(',',$roles_name);

        $data['level_id'] = $this->out['level_id'];
        $data['atime']  = $t;
        $data['uid']    = $this->out['uid'];
        $data['teacher_uid']    = $this->out['teacher_uid'];

        $del_teacher = D('Client/Profile')->delTeacher(['uid'=>$this->out['uid']]);

        $this->model->startTrans();

        if($raw['id'] && is_numeric($raw['id'])){

            $modify = MODIFY;
            $id = $raw['id'];

//更改原审核数据状态
            $res_check = D('Client/Check')->editCheckExt(
                [
                    'status'=>2],
                [
                    'condition' => serialize(['id'=>$raw['id']]),
                    'status'=>1,
                    'table'=>TUSER_APPLY_TEA_REC
                ]
            );
            $res = $this->model->editRecordTea($data, ['id' => $raw['id']]);

            if(!$res ){
                $this->model->rollback();
                $ret['status'] = E_SYSTEM;
                $ret['errstr'] = 'edit failed';
                goto END;
            }

        }else{
            $modify = ADD;
            $res = $this->model->addRecordTea($data);

//添加教员信息
            $teacher_data = [
                'uid'=>$this->out['uid'],
                'name'=>$this->out['nickname'],
                'atime'=>time(),
                'quota_left'=>C('TEACHER_QUOTA'),
                'quota_total'=>C('TEACHER_QUOTA'),
                'status'=>2

            ];
            $add_teacher = D('Admin/Classroom')->addTeacher($teacher_data);

            if( !$res  || !$add_teacher){
                $this->model->rollback();
                $ret['status'] = E_STATUS;
                $ret['errstr'] = 'add failed';
                goto END;
            }

            $ret['id'] = $id = $res;

        }


//审核数据
        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid'],'group_id'=>ROLE_DB_TEA]),
            'update'   => 2,
            'tid' =>0,
            'table' => TROLE_USER,
            'data'  => serialize(['status'=>SYS_OK]),
        ];
//审核拒绝 恢复当前状态
        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'update'   => 1,
            'tid'   => C_BACK_STATUS,
            'table' => TUSER,
            'data'  => serialize(['status_te'=>$this->out['status_te']])
        ];
        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid'],'group_id'=>ROLE_DB_NOR]),
            'update' => 2,
            'tid' =>0,
            'table' => TROLE_USER,
            'data'  => serialize(['status'=>SYS_FORBID]),
        ];
        $check['data_ext'][] = [
            'condition'   => serialize(['id'=>$id]),
            'update'   => 1,
            'tid' =>0,
            'table' => TUSER_APPLY_TEA_REC,
            'data'  => ''
        ];
        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'update'   => 2,
            'tid' =>0,
            'table' => TUSER,
            'data'  => serialize(['roles'=>$user_roles,'status_te'=>SYS_OK])
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'update'   => 2,
            'tid' =>0,
            'table' => TUSER_T,
            'data'  => serialize(['status'=>SYS_OK])
        ];

        $check['data'] = [
            'uid'    => $this->out['uid'],
            'modify' => $modify,
            'type' => C_APP_TE,
            'atime'  => time(),
            'desc'   => '',
        ];


//添加审核数据
        $check_id = A('Check')->addCheck($check, 1);
        $data1['check_id'] = $check_id;
        $data1['mtime'] = null;
        $data1['check'] = LINE;
        $res_record_tea = $this->model->editRecordTea($data1, ['id' => $id]);

        $res_status = $this->modelProfile->saveClient(
            ['uid'=>$this->out['uid']],
            [
                'status_te' =>CHG_LINE
            ]
        );
        if(!$res_record_tea || !$check_id  || !$res_status){
            $this->model->rollback();
            $ret['status'] = E_SYSTEM;
            $ret['errstr'] = 'edit failed';
            goto END;
        }

        $this->model->commit();

END:
        $this->retReturn($ret);

    }


    /**
     * 申请成为教员列表
     */

    public function showApplyTeaList(){

        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:C('PAGE_LIMIT');
        $limit = $num*($page-1).','.$num;

        $where['uid']= $this->out['uid'];

        $res = $this->model->selectRecordTea(
            '',
            $where,
            $limit,
            'id desc'
        );

        if(!$res)
            goto END;

        $total = $this->model->selectRecordTea('count(*) total',$where);


        $ret['total']  = $total[0]['total'];
        $ret['page_n'] = count($res);
        $ret['page_start'] = $page;
        $ret['data'] = $res;

END:

        $this->retReturn($ret);

    }



    /**
     *申请教员记录详情
     */
    public function showApplyTeaDetail()
    {
        $ret = [];

        $raw = $this->RxData;

        if(!$raw['id'])
            goto END;

        $exist = $this->model->findRecordTea('', ['id'=>$raw['id']]);
        if(!$exist){
            $ret['status'] = E_NOEXIST;
            $ret['errstr']  = 'not exist';
            goto END;
        }

        if(!$exist)
            goto END;

        $teacher_new = $this->model->findTeacher('b.nickname,b.icon,b.xiaozhu,b.uid',['a.uid'=>$exist['new_teacher_uid']]);

        $exist['teacher_name'] = $this->out['teacher'];
        $exist['new_teacher_name']  = $teacher_new['nickname'];
        $exist['new_teacher_uid']   = $teacher_new['uid'];

        $ret = $exist;


END:
        $this->retReturn($ret);
    }


    /**
     * 学员变更教员
     */

    public function changeTeacher(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_OK, 'errstr' => '' ];
        $t = time();


        //判断状态
        $exist_chg = $this->model->findRecordChgTea(
            '',
            ['uid'=>$this->out['uid'],'type'=>0],
            'id desc'
        );


        if($exist_chg && $exist_chg['status'] == PASS){
            $ret['status'] = E_STATUS;
            $ret['errstr']  = 'already done';
            goto END;
        }

        //新教员信息
        $teacher_new = $this->modelProfile->findTeacher('',['uid'=>$raw['new_teacher_uid']]);
        if($teacher_new['quota_left']  == 0){

            $ret['status'] = E_NOTENOUGH;
            $ret['errstr']  = 'lack quota';
            goto END;
        }

        $keys_m = ['new_teacher_uid'];
        $keys = ['new_teacher_uid'];
        foreach($keys_m as $v){
            if(!isset($raw[$v]) || $raw[$v] == null){
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
        }
        $data = [];
        foreach($keys as $v){
            if(isset($raw[$v])){
                $data[$v] = $raw[$v];
            }
        }

        if($raw['id'] && is_numeric($raw['id']) ) {
            $exist = $this->model->findRecordChgTea('', ['id' => $raw['id']]);
            if(!$exist){
                $ret['status'] = E_NOEXIST;
                $ret['errstr'] = 'not exists';
                goto END;
            }

            if($exist['check'] != LINE){
                $ret['status'] = E_AC;
                $ret['errstr'] = 'can not change a stable data';
                goto END;
            }

        }else{

            if($exist_chg && $exist_chg['check'] == LINE){
                $ret['status'] = E_EXIST;
                $ret['errstr']  = 'waiting please';
                goto END;
            }

        }

        $data['teacher_uid'] = $this->out['teacher_uid'];
        $data['atime'] = $t;
        $data['uid'] = $this->out['uid'];


        $this->model->startTrans();

        if($raw['id'] && is_numeric($raw['id'])){
            //edit
            $modify = MODIFY;
            $id = $raw['id'];
            $res = $this->model->editRecordChgTea($data, ['id' => $raw['id']]);
            if(!$res){
                $this->model->rollback();
                $ret['status'] = E_SYSTEM;
                $ret['errstr'] = 'edit failed';
                goto END;
            }

        }else{

            $modify = ADD;
            $res = $this->model->addRecordChgTea($data);
            if( !$res){
                $this->model->rollback();
                $ret['status'] = E_STATUS;
                $ret['errstr'] = 'add failed';
                goto END;
            }
            $id = $res;
            $ret['id'] = $res;
        }

//原教员名额+1
        $res_now = $this->modelProfile->incTeacher(
            ['column'=>'quota_left','value'=>1],
            ['uid'=>$this->out['teacher_uid']])
        ;
//新教员名额-1
        $res_new = $this->modelProfile->decTeacher(
            ['column'=>'quota_left','value'=>1],
            ['uid'=>$raw['new_teacher_uid']])
        ;

//审核
        $check['data_ext'][] = [
            'condition'   =>serialize(['uid'=>$this->out['teacher_uid']]),
            'update'   => 2,
            'tid'   => 1,
            'table' => TUSER_T,
            'data'  => '',
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$raw['new_teacher_uid']]),
            'update'   => 2,
            'tid'   => 2,
            'table' => TUSER_T,
            'data'  => '',
        ];

        $check['data'] = [
            'uid' => $this->out['uid'],
            'update'   => 1,
            'modify' => $modify,
            'type'   => C_CHG_TE,
            'atime'  => time()
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['id'=>$id]),
            'update'   => 1,
            'tid'   => 0,
            'table' => TUSER_CHGTEA_REC,
            'data'  => '',
        ];


        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'update'   => 2,
            'tid'   => '0',
            'table' => TUSER,
            'data'  => serialize(['teacher_uid'=>$raw['new_teacher_uid']]),
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'update'   => 2,
            'tid'   => 3,
            'table' => TUSER,
            'data'  => ''
        ];

        $res_check = D('Client/Check')->editCheckExt(
            [
                'status'=>2],
            [
                'condition' => serialize(['id'=>$raw['id']]),
                'status'=>1,
                'table'=>TUSER_CHGTEA_REC
            ]
        );

        $res_check = D('Client/Check')->editCheckExt(
            [
                'status'=>2],
            [
                'condition' => serialize(['uid'=>$teacher_new['uid']]),
                'status'=>1,
                'update'=> 2,
                'table'=>TUSER_T
            ]
        );
        $res_check = D('Client/Check')->editCheckExt(
            [
                'status'=>2],
            [
                'condition' => serialize(['uid'=>$this->out['teacher_uid']]),
                'status'=>1,
                'update'=> 2,
                'table'=>TUSER_T
            ]
        );

        $res_check = D('Client/Check')->editCheckExt(
            [
                'status'=>2],
            [
                'condition' => serialize(['uid'=>$this->out['uid']]),
                'status'=>1,
                'update'=> 2,
                'table'=>TUSER
            ]
        );

        $res_check = D('Client/Check')->editCheckExt(
            [
                'status'=>2],
            [
                'condition' => serialize(['id'=>$raw['id']]),
                'status'=>1,
                'table'=>TUSER_CHGTEA_REC
            ]
        );

        //审核
        $check_id = A('Check')->addCheck($check, 1);

        $data1['check_id'] = $check_id;
        $data1['mtime']  = null;
        $data1['check'] = LINE;
        $res = $this->model->editRecordChgTea($data1, ['id' => $id]);

        if(!$res || !$check_id){
            $this->model->rollback();
            $ret['status'] = E_SYSTEM;
            $ret['errstr'] = 'edit failed';
            goto END;
        }
        $this->model->commit();

END:
        $this->retReturn($ret);

    }


    /**
     * 申请变更教员列表
     */

    public function showChangeTeaList(){

        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:C('PAGE_LIMIT');
        $limit = $num*($page-1).','.$num;
        $where['uid']= $this->out['uid'];
        $where['type']= 0;

        $res = $this->model->selectRecordChgTea(
            '',
            $where,
            $limit,
            'id desc'
        );

        if(!$res)
            goto END;

        foreach($res as $v){
            $uids[]     = $v['teacher_uid'];
            $uids[]     = $v['new_teacher_uid'];
        }

        $teacher_info = $this->modelProfile->selectClient('icon,uid,nickname,name',['uid'=>['in',$uids]]);

        $user = [];
        if($teacher_info)
            foreach ($teacher_info as $val) {
                $user[$val['uid']] = [
                    'uid'   =>  $val['uid'],
                    'name'  =>  $val['name'],
                    'nickname'  =>  $val['nickname'],
                    'icon'   =>  \Common\GetCompleteUrl($val['icon'])
                ];
            }

        foreach ($res as &$re) {
            $re['teacher_name'] = $user[$re['teacher_uid']]['nickname'];
            $re['new_teacher_name'] = $user[$re['new_teacher_uid']]['nickname'];
        }

        $total = $this->model->selectRecordChgTea('count(*) total',$where);


        $ret['total']  = $total[0]['total'];
        $ret['page_n'] = count($res);
        $ret['page_start'] = $page;
        $ret['data'] = $res;
END:

        $this->retReturn($ret);

    }

    /**
     * 绑定教员记录
     */

    public function showChangeTeaRecord(){

        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:C('PAGE_LIMIT');
        $limit = $num*($page-1).','.$num;
        $where['uid']= $this->out['uid'];
        $where['check'] = PASS;

        $res = $this->model->selectRecordChgTea(
            'id,teacher_uid,new_teacher_uid,uid,mtime',
            $where,
            $limit,
            'id desc'
        );


        if(!$res)
            goto END;

        foreach($res as $v){
            $uids[]     = $v['uid'];
            $uids[]     = $v['teacher_uid'];
            $uids[]     = $v['new_teacher_uid'];
        }

        $user = \Common\getUserInfo($uids);
        $fin = $fin_new = [];
        if(count($res) > 1){
            foreach ($res as $k=>&$re1) {
                if(!$re1['new_teacher_uid'])
                    continue;
                $re1['name'] = $user[$re1['new_teacher_uid']]['name'];
                $re1['nickname'] = $user[$re1['new_teacher_uid']]['nickname'];
                $re1['xiaozhu'] = $user[$re1['new_teacher_uid']]['xiaozhu'];
                $re1['qq'] = $user[$re1['new_teacher_uid']]['qq'];
                $re1['level'] = $user[$re1['new_teacher_uid']]['level'];
                $re1['atime'] = $re1['mtime'];
                $re1['btime'] = $res[$k-1]['mtime'];
                $fin[] = $re1;
            }
        }else{
            $res = $res[0];
            $re1['name'] = $user[$res['new_teacher_uid']]['name'];
            $re1['nickname'] = $user[$res['new_teacher_uid']]['nickname'];
            $re1['xiaozhu'] = $user[$res['new_teacher_uid']]['xiaozhu'];
            $re1['qq'] = $user[$res['new_teacher_uid']]['qq'];
            $re1['level'] = $user[$res['new_teacher_uid']]['level'];
            $re1['atime'] = $res['mtime'];
            $re1['btime'] = '';
            $fin[] = $re1;
        }

        //退教记录只使用其时间，不参与统计
        $where['new_teacher_uid'] = ['gt',0];
        $total = $this->model->selectRecordChgTea('count(*) total',$where);

        $ret['total']  = $total[0]['total'];
        $ret['page_n'] = count($fin);
        $ret['page_start'] = $page;
        $ret['data'] = $fin;
END:

        $this->retReturn($ret);

    }


    /**
     * 申请变更教员详情
     */

    public function showChangeTeaDetail(){

        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:C('PAGE_LIMIT');
        $limit = $num*($page-1).','.$num;


        $where['uid']= $this->out['uid'];

        $res = $this->model->selectRecordChgTea(
            '',
            $where,
            $limit,
            'id desc'
        );

        if(!$res)
            goto END;

        foreach($res as $v){
            $uids[]     = $v['teacher_uid'];
            $uids[]     = $v['new_teacher_uid'];
        }

        $teacher_info = $this->modelProfile->selectClient('icon,uid,nickname,name',['uid'=>['in',$uids]]);

        $user = [];

        if($teacher_info)
            foreach ($teacher_info as $val) {
                $user[$val['uid']] = [
                    'uid'   =>  $val['uid'],
                    'name'  =>  $val['name'],
                    'nickname'  =>  $val['nickname'],
                    'icon'   =>  \Common\GetCompleteUrl($val['icon'])
                ];
            }

        foreach ($res as &$re) {
            $re['teacher_name'] = $user[$re['teacher_uid']]['nickname'];
            $re['new_teacher_name'] = $user[$re['new_teacher_uid']]['nickname'];
        }

        $total = $this->model->selectRecordChgTea('count(*) total',$where);


        $ret['total']  = $total[0]['total'];
        $ret['page_n'] = count($res);
        $ret['page_start'] = $page;
        $ret['data'] = $res;
END:

        $this->retReturn($ret);

    }


    /**
     * 为学员升星
     */

    public function upgrade(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_OK, 'errstr' => '' ];
        $t = time();

//判断教员角色
        $roles_arr = [
            ROLE_BIN_TEA,
            ROLE_BIN_TEA+ROLE_BIN_NOR,
            ROLE_BIN_TEA+ROLE_BIN_STU,
            ROLE_BIN_TEA+ROLE_BIN_STU+ROLE_BIN_NOR
        ];
        if(!in_array($this->out['admin'],$roles_arr)){
            $ret['status'] = E_STATUS;
            $ret['errstr']  = '';
            goto END;
        }
//判断状态
        $exist = $this->model->findRecordUpgrade(
            '',
            [
                'uid'   =>$raw['uid']
            ]
        );

        if($exist && $exist['check'] == PASS){
            $ret['status'] = E_STATUS;
            $ret['errstr']  = 'already upgraded';
            goto END;
        }

        if($exist && $exist['check'] != REJECT){
            $ret['status'] = E_EXIST;
            $ret['errstr']  = 'waitting please!';
            goto END;
        }

        $keys_m = ['uid'];
        foreach($keys_m as $v){
            if(!isset($raw[$v]) || $raw[$v] == null){
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
        }
        $data = [];

        if($raw['id'] && is_numeric($raw['id']) ) {
            $exist = $this->model->findRecordUpgrade('', ['id' => $raw['id']]);
            if(!$exist){
                $ret['status'] = E_NOEXIST;
                $ret['errstr'] = 'not exists';
                goto END;
            }

            if($exist['check'] != REJECT){
                $ret['status'] = E_AC;
                $ret['errstr'] = 'can not change a stable data';
                goto END;
            }

        }else{
            if($exist && $exist['check'] == LINE){
                $ret['status'] = E_EXIST;
                $ret['errstr']  = 'waiting please1';
                goto END;
            }
        }

        $data['atime'] = $t;
        $data['teacher_uid'] = $this->out['uid'];
        $data['uid']  = $raw['uid'];

//学员信息
        $stu_info  =  $this->modelProfile->findClient('level_id,nickname,teacher_uid',['uid'=>$raw['uid']]);
        $data['level_id']  = $stu_info['level_id'];
        $data['new_level_id']  = $stu_info['level_id']+1;

//新教员
        $teacher_info = '';
        if($this->out['level_id'] <= $data['new_level_id']){
            $level_limit = D('Admin/System')->selectLevel('max(id) as id');
            $teacher_info = $this->findNewTeacher($data['new_level_id'],$level_limit[0]['id']);

        }

        $data['new_teacher_uid'] = $teacher_info?'':$this->out['teacher_uid'];


        $this->model->startTrans();

        if($raw['id'] && is_numeric($raw['id'])){
            $id = $raw['id'];
            $res = $this->model->editRecordUpgrade($data, ['id' => $raw['id']]);

            if(!$res ){
                $this->model->rollback();
                $ret['status'] = E_SYSTEM;
                $ret['errstr'] = 'edit failed';
                goto END;
            }
        }else{

            $res = $this->model->addRecordUpgrade($data);
            $modify = ADD;
            $id = $res;
        }

//审核
        $check['data'] = [
            'uid' => $this->out['uid'],
            'modify' => $modify,
            'type'   => C_UPGRADE,
            'atime'  => time()
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$raw['uid']]),
            'tid' => 0,
            'table' => TUSER,
            'update' => 2,
            'data'  => serialize(['level_id'=>($stu_info['level_id']+1)]),
        ];

        $check['data_ext'][] = [
            'condition' => serialize(['id'=>$id]),
            'tid'   => 0,
            'table' => TUSER_UPGRADE_REC,
            'update'=> 1,
            'data'  => '',
        ];

        if($teacher_info){
//原教员名额+1
            $res_now = $this->modelProfile->incTeacher(
                ['column'=>'quota_left','value'=>1],
                ['uid'=>$stu_info['teacher_uid']])
            ;
            $check['data_ext'][] = [
                'condition'   => serialize(['uid'=>$stu_info['teacher_uid']]),
                'tid'   => 1,
                'table' => TUSER_T,
                'update' => 2,
                'data'  => '',
            ];
        }

        $ret['id'] = $res;
        if( !$res){
            $this->model->rollback();
            $ret['status'] = E_STATUS;
            $ret['errstr'] = 'add failed';
            goto END;
        }


        $check = A('Client/Check')->addCheck($check, 1);

        $data1['check_id'] = $check;
        $data1['check']    = LINE;
        $res  = $this->model->editRecordUpgrade($data1, ['id' => $res]);
        if( !$res || !$check){
            $this->model->rollback();
            $ret['status'] = E_STATUS;
            $ret['errstr'] = 'add failed';
            goto END;
        }


        $this->model->commit();

END:
        $this->retReturn($ret);

    }

    /*
     * 获取教员列表
     */
    protected function findNewTeacher($level,$level_limit){
        $level++;
        $teacher_info = $this->model->findTeacher(
            'b.uid',
            ['b.level_id'=>$level,'a.status'=>1],
            '',
            'a.quota_left desc'
        );

        if(!$teacher_info && $level<=$level_limit){
            $this->findNewTeacher($level,$level_limit);
        }
        return $teacher_info;

    }


    /**
     * 为学员升星记录
     */

    public function showUpgradeList(){

        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:C('PAGE_LIMIT');
        $limit = $num*($page-1).','.$num;

        if($raw['check'])
            $where['a.check']= $raw['check'];
        if($raw['level_id'])
            $where['a.level_id']= $raw['level_id'];
        if($raw['level_id'])
            $where['a.level_id']= $raw['level_id'];


        if ($raw['nickname'])
            $where['b.nickname'] = ['like','%'.$raw['nickname'].'%'];

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
        $where['a.teacher_uid']= $this->out['uid'];

        $res = $this->model->selectRecordUpgrade(
            'a.*,b.nickname,b.uid,b.xiaozhu,b.icon',
            $where,
            $limit,
            'a.id desc'
        );

        if(!$res)
            goto END;

        $total = $this->model->selectRecordUpgrade('count(*) total',$where);
//等级
        $level = \Common\getLevelInfo();;

        foreach($res as &$v){
            $v['level_org'] = $level[$v['level_id']]['name'];
            $v['level_to'] = $level[$v['new_level_id']]['name'];
        }


        $ret['total']  = $total[0]['total'];
        $ret['page_n'] = count($res);
        $ret['page_start'] = $page;
        $ret['data'] = $res;
END:

        $this->retReturn($ret);

    }


    public function delImg()
    {
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        if(!$raw['id'])
            goto END;

        $where = ['id'=>$raw['id']];
        $res = $this->model->findAttach('', $where);

        foreach ($res as $v) {
            \Common\FileDelete($v['path']);
        }

        $res = $this->model->delAttach($where);

        if (!$res)
            goto END;

        $ret['status'] = E_OK;

END:
        $this->retReturn($ret);
    }



    /**
     * upload
     */
    public function upload()
    {

        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $uid = $this->out['uid'];
        $raw = $this->RxData;

        $field = 'picture';
        $pre_path = C("UPLOAD_PATH");

        $file1 = 'pics';
        $realpath = $pre_path . 'resource' . "/" . $file1 . '/uid_' . $uid . '/' . date('Y-m-d', time()) . '/';

        $conf = array(
            'pre'      => 'hwk',
            'types' => [
                'jpg', 'gif', 'png', 'jpeg',
                'mp4', 'avi', 'wmv',
                'doc', 'ppt', 'pptx','xlsx','xls',
                'xml', 'txt', 'pdf']
        );

        if (!is_dir($realpath)) $z = mkdir($realpath, 0775, true);
        $upload_res = \Common\_Upload($field, $realpath, $conf);

        if ($upload_res['status'] != 0) {
            $ret = $upload_res;
            goto END;
        }

        foreach ($upload_res['file'] as $key => $value) {

            $file_path = $value['savepath'] . $value['savename'];
            $path = $realpath . $value['savename'];
            $save_name = $value['savename'];
        }

        $data = [
            'path'  => $path,
            'atime' => time(),
            'name'  => $save_name

        ];
        $res = $this->model->addAttach($data);
        if (!$res)
            goto END;

        $ret['id']       = $res;
        $ret['status']   = E_OK;
        $ret['errstr']   = '';
        $ret['url'] = \Common\GetCompleteUrl($path);
        $ret['path']     =  $path;
END:
        $this->retReturn($ret);
    }

    /**
     * 修改身份状态
     */

    public function setStudyStatus(){
        $ret = [ 'status' => E_OK, 'errstr' => '' ];
        $raw = $this->RxData;
        $keys_m = ['type'];
        foreach ($keys_m as $val) {
            if (!isset($raw[$val]) || $raw[$val] === '') {
                $ret['status'] = E_DATA;
                goto END;
            }
        }
        //1退学；2休学；3休教;4、恢复教员；5、恢复教员;6恢复学员",

        if(in_array($raw['type'],[1,2,6] )){
            $roles_arr = [
                ROLE_BIN_STU,
                ROLE_BIN_STU+ROLE_BIN_NOR,
                ROLE_BIN_TEA+ROLE_BIN_STU,
                ROLE_BIN_TEA+ROLE_BIN_STU+ROLE_BIN_NOR
            ];
        }else{
            $roles_arr = [
                ROLE_BIN_TEA,
                ROLE_BIN_TEA+ROLE_BIN_NOR,
                ROLE_BIN_TEA+ROLE_BIN_STU,
                ROLE_BIN_TEA+ROLE_BIN_STU+ROLE_BIN_NOR
            ];
        }
        $exist = $this->model->findRecordChgStatus('check',['uid'=>$this->out['uid'],'type'=>$raw['type']]);
        /*    if(in_array($this->out['admin'],$roles_arr) && $exist['check'] == PASS){
                $ret['status'] = E_EXIST;
                $ret['errstr'] = 'already done';
                goto END;
            }else*/

        if(in_array($this->out['admin'],$roles_arr)  && $exist['check'] == LINE){
            $ret['status'] = E_EXIST;
            $ret['errstr'] = 'waiting please22';
            goto END;
        }
        //1退学；2休学；3休教;4、恢复教员；5、恢复教员;6恢复学员",
        switch($raw['type']){
            case 1:
                $this->quitSchool();
                break;
            case 2:
                $this->breakSchool();
                break;
            case 3:
                $this->breakTeach();
                break;
            case 4:
                $this->quitTeach();
                break;
            case 5:
                $this->backTeach();
                break;
            case 6:
                $this->backSchool();
                break;
            default:
                goto END;
        }

END:
        $this->retReturn( $ret );
    }


    /**
     * 身份状态变更记录
     */

    public function changeStudyStatusList(){

        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];


        if($raw['check'])
            $where['check']= $raw['check'];
        if($raw['type'])
            $where['type']= $raw['type'];
        $page = $raw['page_start'] ? $raw['page_start'] : 1;


        $where['uid']= $this->out['uid'];

        $num  = $raw['page_limit'] ? $raw['page_limit'] : C('PAGE_LIMIT');
        $limit = $num * ($page-1).','.$num;
        $res = $this->model->selectRecordChgStatus('',$where,$limit,'id desc');
        if(!$res)
            goto END;
        $total = $this->model->selectRecordChgStatus('count(*) total',$where,'','id desc');


        $ret['page_start'] = $page;
        $ret['total']  = $total[0]['total'];
        $ret['page_n'] = count($res);
        $ret['data']   = $res;


END:
        $this->retReturn( $ret );
    }

    /**
     * 退学
     */

    private function quitSchool(){

//双重身份
        $roles_tea = [
            ROLE_BIN_TEA,
            ROLE_BIN_TEA+ROLE_BIN_NOR,
            ROLE_BIN_TEA+ROLE_BIN_STU,
            ROLE_BIN_NOR+ROLE_BIN_STU+ROLE_BIN_TEA
        ];
        if(in_array($this->out['admin'],$roles_tea)){
            $ret['status'] = E_STATUS;
            $ret['errstr'] = 'quit teacher please';
            goto END;
        }

        $roles_tea = [
            ROLE_BIN_STU,
            ROLE_BIN_NOR+ROLE_BIN_STU,
            ROLE_BIN_TEA+ROLE_BIN_STU,
            ROLE_BIN_NOR+ROLE_BIN_STU+ROLE_BIN_TEA
        ];

        if(!in_array($this->out['admin'],$roles_tea)){
            $ret['status'] = E_AC;
            $ret['errstr'] = 'no access';
            goto END;
        }


        $this->modelProfile->startTrans();
        $this->modelSystem->startTrans();
        $this->model->startTrans();

        $data0 = [
            'uid' =>$this->out['uid'],
            'type'=>CHG_QUIT_S,
            'check'=>PASS,
            'mtime'=>time(),
            'atime'=>time(),
            'level_id'   =>$this->out['level_id'],
            'teacher_uid'=>$this->out['teacher_uid'],
        ];

        $res = $this->model->addRecordChgStatus($data0);

        $res1 = $this->modelSystem->delRoleGroupUser(
            [
                'uid'=>$this->out['uid'],
                'group_id'=>ROLE_DB_STU
            ]
        );

        $res2 = $this->modelSystem->saveUserRole(
            ['status'=>SYS_OK],
            [
                'uid'=>$this->out['uid'],
                'group_id'=>ROLE_DB_NOR
            ]
        );

        $user_role_info = D('Admin/System')->selectUserRoleGroup(
            '',['a.uid'=>$this->out['uid'],'a.status'=>SYS_OK]);
//添加解绑教员记录
        if($this->out['teacher_uid']){

            $data_t = [
                'teacher_uid'=>$this->out['teacher_uid'],
                'uid'=>$this->out['uid'],
                'atime'=>time(),
                'mtime'=>time(),
                'check'=>PASS,
                'type'=>UNTIE,
            ];
            $res_tie = $this->model->addRecordChgTea($data_t);
        }


//角色
        $roles = [];
        foreach($user_role_info as $v){
            $roles[]= $v['name'];
        }

        $res_chg_status = $this->modelProfile->saveClient(
            ['uid'=>$this->out['uid']],
            [
                'teacher_uid'=>'','roles'=>implode(',',$roles),
                'status_stu' =>STUDY_QUIT,
                'level_id' =>ROLE_DB_NOR,
            ]
        );

        /*       if(!$res1 || !$res2 || !$res3){

                   $this->modelProfile->rollback();
                   $this->modelSystem->rollback();
                   $ret['status'] = E_SYSTEM;
                   $ret['errstr'] = '3';
                   goto END;
               }*/

        $this->model->commit();

        $ret['status'] = E_OK;

END:
        $this->retReturn( $ret );

    }

    /**
     * 休学
     */

    private function breakSchool(){

        $roles = [
            ROLE_BIN_STU,
            ROLE_BIN_NOR+ROLE_BIN_STU,
            ROLE_BIN_TEA+ROLE_BIN_STU,
            ROLE_BIN_NOR+ROLE_BIN_STU+ROLE_BIN_TEA
        ];

        if(!in_array($this->out['admin'],$roles)){
            $ret['status'] = E_AC;
            $ret['errstr'] = 'no access';
            goto END;
        }
        $this->model->startTrans();


        $data = [
            'uid' =>$this->out['uid'],
            'type'=>CHG_BREAK_S,
            'check'=>PASS,
            'mtime'=>time(),
            'atime'=>time(),
            'level_id'=>$this->out['level_id'],
            'teacher_uid'=>$this->out['teacher_uid'],
        ];

        $res = $this->model->addRecordChgStatus($data);


//添加解绑教员记录
        $data_t = [
            'teacher_uid'=>$this->out['teacher_uid'],
            'uid'=>$this->out['uid'],
            'atime'=>time(),
            'type'=>UNTIE,
            'check'=>PASS,
            'mtime'=>time(),
            'atime'=>time(),
        ];
        $res_tie = $this->model->addRecordChgTea($data_t);
        $res_chg_status = $this->modelProfile->saveClient(
            ['uid'=>$this->out['uid']],
            [
                'status_stu'=>STUDY_BREAK
            ]
        );
        if(!$res_chg_status){

            $this->model->rollback();
            $ret['status'] = E_SYSTEM;
            goto END;
        }

        $this->model->commit();
        $ret['status'] = E_OK;

END:
        $this->retReturn( $ret );

    }

    /**
     * 恢复学籍
     */

    private function backSchool(){

        $roles = [
            ROLE_BIN_STU,
            ROLE_BIN_NOR+ROLE_BIN_STU,
            ROLE_BIN_TEA+ROLE_BIN_STU,
            ROLE_BIN_NOR+ROLE_BIN_STU+ROLE_BIN_TEA
        ];

        if($this->out['status_stu'] != STUDY_BREAK){
            $ret['status'] = E_AC;
            $ret['errstr'] = 'no access';
            goto END;
        }
        $this->model->startTrans();

        $data = [
            'uid' =>$this->out['uid'],
            'type'=>CHG_BACK_S,
            'check'=>PASS,
            'mtime'=>time(),
            'atime'=>time(),
            'level_id'=>$this->out['level_id'],
            'teacher_uid'=>$this->out['teacher_uid'],
        ];

        $res = $this->model->addRecordChgStatus($data);

        $res_chg_status = $this->modelProfile->saveClient(
            ['uid'=>$this->out['uid']],
            [
                'status_stu'=>SYS_OK
            ]
        );
        if(!$res_chg_status){

            $this->model->rollback();
            $ret['status'] = E_SYSTEM;
            goto END;
        }

        $this->model->commit();
        $ret['status'] = E_OK;

END:
        $this->retReturn( $ret );

    }


    /**
     * 退教
     */

    private function quitTeach(){

        $roles_te = [
            ROLE_BIN_TEA,
            ROLE_BIN_TEA+ROLE_BIN_NOR,
            ROLE_BIN_TEA+ROLE_BIN_STU,
            ROLE_BIN_NOR+ROLE_BIN_STU+ROLE_BIN_TEA
        ];

        if(!in_array($this->out['admin'],$roles_te)){
            $ret['status'] = E_AC;
            $ret['errstr'] = 'u r not a teacher';
            goto END;
        }

        $check_stu = $this->modelProfile->findClient('',['teacher_uid'=>$this->out['uid']]);
        if($check_stu){
            $ret['status'] = E_NOEXIST;
            $ret['errstr'] = 'still has students';
            goto END;
        }
        $data = [
            'uid' =>$this->out['uid'],
            'type'=>CHG_QUIT_T,
            'atime'=>time(),
            'level_id'=>$this->out['level_id'],
            'teacher_uid'=>$this->out['teacher_uid'],
        ];
        $this->model->startTrans();


        $res = $this->model->addRecordChgStatus($data);

        if(!$res ){
            $this->model->rollback();
            $ret['status'] = E_SYSTEM;
            $ret['errstr'] = 'edit failed';
            goto END;
        }

        //角色
        $roles = [];
        $user_role_info = D('Admin/System')->selectUserRoleGroup(
            '',['a.uid'=>$this->out['uid'],'a.status'=>SYS_OK,'a.group_id'=>['neq',ROLE_DB_TEA]]);
        foreach($user_role_info as $v){
            $roles[]= $v['name'];
        }

        $res6 = $this->modelProfile->saveClientMine(['status_te'=>CHG_LINE],['uid'=>$this->out['uid']]);

//审核数据

        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'tid'   => 0,
            'update'   => 2,
            'table' => TUSER,
            'data'  => serialize(['status_te'=>TEACH_QUIT,'roles'=>implode(',',$roles)])
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'tid'   => C_BACK_STATUS,
            'update'   => 1,
            'table' => TUSER,
            'data'  => serialize(['status_te'=>$this->out['status_te']])
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['id'=>$res]),
            'tid'   => 0,
            'update'   => 1,
            'table' => TUSER_CHG_STATUS_REC,
            'data'  => ''
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'tid'   => 0,
            'update'   => 2,
            'table' => TUSER_T,
            'data'  => serialize(['status'=>SYS_FORBID])
        ];

        $check['data'] = [
            'uid'    => $this->out['uid'],
            'modify' => ADD,
            'type' => C_QUIT_TE,
            'atime'  => time(),
            'desc'   => '',
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid'],'group_id'=>ROLE_DB_TEA]),
            'tid'   => 0,
            'update'   => 2,
            'table' => TROLE_USER,
            'data'  => serialize(['status'=>SYS_FORBID])
        ];

//添加审核数据
        $check_id = A('Check')->addCheck($check, 1);
        $data1['check_id'] = $check_id;
        $data1['mtime'] = null;
        $data1['check'] = LINE;
        $res2 = $this->model->editRecordChgStatus($data1, ['id' => $res]);

        if(!$res2 || !$check_id){
            $this->model->rollback();
            $ret['status'] = E_SYSTEM;
            $ret['errstr'] = 'edit failed';
            goto END;
        }

        $this->model->commit();
        $ret['status'] = E_OK;

END:
        $this->retReturn( $ret );

    }


    /**
     * 休教
     */

    private function breakTeach(){

        $roles = [
            ROLE_BIN_TEA,
            ROLE_BIN_TEA+ROLE_BIN_NOR,
            ROLE_BIN_TEA+ROLE_BIN_STU,
            ROLE_BIN_NOR+ROLE_BIN_STU+ROLE_BIN_TEA
        ];

        if(!in_array($this->out['admin'],$roles)){
            $ret['status'] = E_AC;
            $ret['errstr'] = 'u r not a teacher';
            goto END;
        }

        $data = [
            'uid' =>$this->out['uid'],
            'type'=>CHG_BREAK_T,
            'atime'=>time(),
            'level_id'=>$this->out['level_id'],
            'teacher_uid'=>$this->out['teacher_uid'],
        ];
        $this->model->startTrans();


        $res = $this->model->addRecordChgStatus($data);
        $res1 = $this->modelProfile->saveClientMine(['status_te'=>CHG_LINE],['uid'=>$this->out['uid']]);

        if(!$res ){
            $this->model->rollback();
            $ret['status'] = E_SYSTEM;
            $ret['errstr'] = 'edit failed';
            goto END;
        }
//审核数据

        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'tid'   => 0,
            'update'   => 2,
            'table' => TUSER,
            'data'  => serialize(['status_te'=>TEACH_BREAK])
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'tid'   => C_BACK_STATUS,
            'update'   => 1,
            'table' => TUSER,
            'data'  => serialize(['status_te'=>$this->out['status_te']])
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['id'=>$res]),
            'tid'   => 0,
            'update'   => 1,
            'table' => TUSER_CHG_STATUS_REC,
            'data'  => ''
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'tid'   => 0,
            'update'   => 2,
            'table' => TUSER_T,
            'data'  => serialize(['status'=>SYS_FORBID])
        ];

        $check['data'] = [
            'uid'    => $this->out['uid'],
            'modify' => ADD,
            'type' => C_BREAK_TE,
            'atime'  => time(),
            'desc'   => '',
        ];


//添加审核数据
        $check_id = A('Check')->addCheck($check, 1);
        $data1['check_id'] = $check_id;
        $data1['mtime'] = null;
        $data1['check'] = LINE;
        $res2 = $this->model->editRecordChgStatus($data1, ['id' => $res]);

        if(!$res2 || !$check_id){
            $this->model->rollback();
            $ret['status'] = E_SYSTEM;
            $ret['errstr'] = 'edit failed';
            goto END;
        }

        $this->model->commit();
        $ret['status'] = E_OK;

END:
        $this->retReturn( $ret );

    }


    /**
     * 恢复教员
     */
    private function backTeach(){

        $roles = [
            ROLE_BIN_TEA,
            ROLE_BIN_TEA+ROLE_BIN_NOR,
            ROLE_BIN_TEA+ROLE_BIN_STU,
            ROLE_BIN_NOR+ROLE_BIN_STU+ROLE_BIN_TEA
        ];

        if(!in_array($this->out['admin'],$roles)){
            $ret['status'] = E_AC;
            $ret['errstr'] = 'u r not a teacher';
            goto END;
        }

        $data = [
            'uid' =>$this->out['uid'],
            'type'=>CHG_BACK_T,
            'atime'=>time(),
            'level_id'=>$this->out['level_id'],
            'teacher_uid'=>$this->out['teacher_uid'],
        ];

        $this->model->startTrans();



        $res = $this->model->addRecordChgStatus($data);

        $res1 = $this->modelProfile->saveClientMine(['status_te'=>CHG_LINE],['uid'=>$this->out['uid']]);

        if(!$res ){
            $this->model->rollback();
            $ret['status'] = E_SYSTEM;
            $ret['errstr'] = 'edit failed';
            goto END;
        }
//审核数据
        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'tid'   => 0,
            'update'   => 2,
            'table' => TUSER,
            'data'  => serialize(['status_te'=>SYS_OK])
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'tid'   => 0,
            'update'   => 2,
            'table' => TUSER_T,
            'data'  => serialize(['status'=>SYS_OK])
        ];

        $check['data_ext'][] = [
            'condition'   => serialize(['id'=>$res]),
            'tid'   => 0,
            'update'   => 1,
            'table' => TUSER_CHG_STATUS_REC,
            'data'  => ''
        ];
        $check['data_ext'][] = [
            'condition'   => serialize(['uid'=>$this->out['uid']]),
            'tid'   => C_BACK_STATUS,
            'update'   => 1,
            'table' => TUSER,
            'data'  => serialize(['status_te'=>$this->out['status_te']])
        ];
        $check['data'] = [
            'uid'    => $this->out['uid'],
            'modify' => ADD,
            'type' => C_BACK_TE,
            'atime'  => time(),
            'desc'   => ''
        ];


//添加审核数据
        $check_id = A('Check')->addCheck($check, 1);
        $data1['check_id'] = $check_id;
        $data1['mtime'] = null;
        $data1['check'] = LINE;
        $res2 = $this->model->editRecordChgStatus($data1, ['id' => $res]);

        if(!$res2 || !$check_id){
            $this->model->rollback();
            $ret['status'] = E_SYSTEM;
            $ret['errstr'] = 'edit failed';
            goto END;
        }

        $this->model->commit();
        $ret['status'] = E_OK;

END:
        $this->retReturn( $ret );

    }
}
