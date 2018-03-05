<?php

namespace Admin\Controller;

class MembershipController extends GlobalController{


    protected $modelProfile;
    protected $modelCheck;
    protected $modelUser;
    protected $profile;
    protected $model;


    public function _initialize($check = true){
        parent::_initialize($check);

        $this->model        = D('Admin/Membership');
        $this->profile      = D('Admin/Profile');
        $this->modelUser    = D('Client/User');
        $this->modelCheck   = D('Admin/Check');
        $this->modelProfile = D('Client/Profile');
    }

    public function teacherList(){
        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num  = $raw['page_limit'] ? $raw['page_limit'] : C('PAGE_LIMIT');
        $limit = $num * ($page-1).','.$num;

        $where = [];

        $where['a.status'] = SYS_OK;
        $level = 1;
        if(isset($raw['uid']) && is_numeric($raw['uid'])){
            $user_info = $this->modelProfile->findClient('uid,level_id',['uid'=>$raw['uid']]);
            if($user_info)
               $level = $user_info['level_id'];
        }

        if($raw['level_id'])
            $level = $raw['level_id'];

        $level_limit = D('Admin/System')->selectLevel('max(id) as id');

        $result = $this->findNewTeacher($level,$limit,$level_limit[0]['id']);

        if(!$result)
            $result = $this->findNewTeacher($level+1,$limit,$level_limit[0]['id'],true);


//等级
        $level = \Common\getLevelInfo();

        foreach ($result as &$item) {
            $item['level'] = $level[$item['level_id']]['name'];
            $item['icon']  = \Common\GetCompleteUrl($item['icon']);
        }

        $ret['page_start'] = $page;
        $ret['total']  = count($result);
        $ret['page_n'] = count($result);
        $ret['data']   = $result;

END:
        $this->retReturn($ret);

    }


    /*
     * 获取教员列表
     */

    protected function findNewTeacher($level,$limit,$level_limit,$down=false){
        if($down){
            $level--;
        }else{
            $level++;
        }
        $teacher_info = $this->model->selectTeacher(
            'b.uid,b.nickname,b.name,b.icon,a.quota_left,b.level_id',
            ['b.level_id'=>['egt',$level],'b.status_te'=>SYS_OK,'a.status'=>SYS_OK,'a.quota_left'=>['gt',1]],
            $limit,
            'b.level_id asc,a.quota_left desc'
        );

        if(!$teacher_info && $level<$level_limit){
            $this->findNewTeacher($level,$limit,$level_limit,$down);
        }
        return $teacher_info;

    }

    /**
     * 申请记录列表
     */

    public function showCheckList(){

        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        if($raw['check'])
            $where['a.check']= $raw['check'];
        if($raw['level_id'])
            $where['a.level_id']= $raw['level_id'];

        $uid0 =$uid1 = [];

        if ($raw['name']){
            $user_info = $this->modelProfile->selectClient('uid',['name'=>['like','%'.$raw['name'].'%']]);

            if(!$user_info)
                goto END;
            foreach($user_info as $v){
                $uid0[] = $v['uid'];
            }
        }
        if ($raw['nickname']){
            $user_info = $this->modelProfile->selectClient('uid',['nickname'=>['like','%'.$raw['nickname'].'%']]);

            if(!$user_info)
                goto END;
            foreach($user_info as $v){
                $uid1[] = $v['uid'];
            }

            if($raw['name']){
                if(!array_intersect($uid0,$uid1))
                    goto END;
                $uid0 = array_merge($uid0,$uid1);
            }else{
                $uid0 = $uid1;
            }
            unset($uid1);
        }


        $uid3 = $uid4 =$t = [];
        if ($raw['teacher_name']){
            $user_info = $this->modelProfile->selectClient('uid',['name'=>['like','%'.$raw['teacher_name'].'%']]);

            if(!$user_info)
                goto END;
            foreach($user_info as $v){
                $uid3[] = $v['uid'];
            }
        }

        if ($raw['teacher_nickname']){

            $user_info = $this->modelProfile->selectClient('uid',['nickname'=>['like','%'.$raw['teacher_nickname'].'%']]);

            if(!$user_info)
                goto END;
            foreach($user_info as $v){
                $uid4[] = $v['uid'];
            }
            if($raw['teacher_name']){
                if(!array_intersect($uid3,$uid4)){
                    goto END;
                }
                $uid3 = array_merge($uid3,$uid4);
            }else{
                $uid3 = $uid4;
            }
        }

        if($raw['teacher_name'] || $raw['teacher_nickname'])
            $where['a.teacher_uid'] = ['in',$uid3];
        if($raw['name'] || $raw['nickname'])
            $where['a.uid'] = ['in',$uid0];


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


        //等级
        $level_info = $this->modelProfile->selectLevel('id,name');
        $level = [];
        foreach($level_info as $v)
            $level[$v['id']] = $v['name'];

        switch($raw['type']){
            case 3:
                $this->showApplyStuList($raw,$level,$where);
                break;
            case 4:
                $this->showChangeTeaList($raw,$level,$where);
                break;
            case 5:
                $this->showApplyTeaList($raw,$level,$where);
                break;
            case 6:
                $this->showUpgradeList($raw,$level,$where);
                break;
            default:
                $raw['type'] =3;
                $this->showApplyStuList($raw,$level,$where);
                break;
        }

END:
        $this->retReturn($ret);
    }


    /**
     * 申请学员记录列表
     */

    protected function showApplyStuList($raw,$level,$where){

        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:C('PAGE_LIMIT');
        $limit = $num*($page-1).','.$num;


        $column = 'a.id,a.uid,a.level_id,a.teacher_uid,a.reason,a.check,a.atime,a.mtime,b.o_uid';
        $res = $this->model->selectRecord(
            $column,
            $where,
            $limit,
            'a.id desc'
        );

        if(!$res)
            goto END;
        $total = $this->model->selectRecord('count(*) total',$where);

        //用户信息
        $uids = [];
        foreach($res as $k=>$v){
            $uids[] = $v['o_uid'];
            $uids[] = $v['uid'];
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


        foreach($res as $k=>&$v){
            $v['type'] = $raw['type'];
            $v['ouname']   = $user[$v['o_uid']]['nickname'];
            $v['name']     = $user[$v['uid']]['name'];
            $v['nickname'] = $user[$v['uid']]['nickname'];
            $v['teacher_name'] = $user[$v['teacher_uid']]['name'];
            $v['teacher_nickname'] = $user[$v['teacher_uid']]['nickname'];
            $v['level']        = $level[$v['level_id']];//申请时的level_id
        }


        $ret['total']  = $total[0]['total'];
        $ret['page_n'] = count($res);
        $ret['page_start'] = $page;
        $ret['data'] = $res;

END:

        $this->retReturn($ret);

    }


    /**
     *申请学员记录详情
     */
    public function showApplyStuDetail(){
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
        $where['id'] = $raw['id'];

        $result = $this->model->findRecord('',$where);

        if(!$result)
            goto END;

        //用户信息
        $uids = [$result['teacher_uid'],$result['uid']];
        $user_info = $this->modelProfile->selectClient(
            'icon,level_id,uid,nickname,name',
            ['uid'=>['in',$uids]]);
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
        $img_temp =[];
        if($result['sec_img'])
            foreach ( unserialize($result['sec_img']) as $item) {
                $img_temp[] = \Common\GetCompleteUrl($item['path']);

            }
        $result['sec_img'] = $img_temp;
        $result['name']     = $user[$result['uid']]['name'];
        $result['nickname'] = $user[$result['uid']]['nickname'];
        $result['teacher_name'] = $user[$result['teacher_uid']]['name'];
        $result['teacher_nickname'] = $user[$result['teacher_uid']]['nickname'];
        $result['sec_img'] = $img_temp;
        $ret = $result;


END:
        $this->retReturn($ret);
    }


    /**
     * 申请成为教员列表
     */

    protected function showApplyTeaList($raw,$level,$where){

        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:C('PAGE_LIMIT');
        $limit = $num*($page-1).','.$num;

        $res = $this->model->selectRecordTea(
            'a.*,b.o_uid',
            $where,
            $limit,
            'a.id desc'
        );

        if(!$res)
            goto END;

        $total = $this->model->selectRecordTea('count(*) total',$where);

        //用户信息
        $uids = [];
        foreach($res as $k=>$v){
            $uids[] = $v['o_uid'];
            $uids[] = $v['uid'];
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


        foreach ($res as &$v) {
            $v['type']      = $raw['type'];
            $v['ouname']   = $user[$v['o_uid']]['nickname'];
            $v['name']   = $user[$v['uid']]['name'];
            $v['nickname'] = $user[$v['uid']]['nickname'];
            $v['teacher_name'] = $user[$v['teacher_uid']]['name'];
            $v['teacher_nickname'] = $user[$v['teacher_uid']]['nickname'];

            $v['level']        = $level[$v['level_id']];//申请时的level_id
        }


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


        //教员信息
        $teacher_info = $this->modelProfile->findClient('icon,level_id,uid,nickname,name',['uid'=>$exist['teacher_uid']]);

        $exist['name'] = $teacher_info['name'];
        $exist['teacher_name'] = $teacher_info['nickname'];


        $ret = $exist;


END:
        $this->retReturn($ret);
    }


    /**
     *升星详情
     */
    public function showUpgradeDetail(){
        $ret = [];

        $raw = $this->RxData;

        if(!$raw['id'])
            goto END;

        $exist = $this->model->findRecordUpgrade('', ['id'=>$raw['id']]);
        if(!$exist){
            $ret['status'] = E_NOEXIST;
            $ret['errstr']  = 'not exist';
            goto END;
        }

        if(!$exist)
            goto END;

        //用户资料
        $uids[] = $exist['new_teacher_uid'];
        $uids[] = $exist['teacher_uid'];
        $uids[] = $exist['uid'];
        $user_info = $this->modelProfile->selectClient('icon,level_id,uid,nickname,name',['uid'=>['in',$uids]]);
        $user = [];
        if($user_info)
            foreach ($user_info as $val) {
                $user[$val['uid']] = [
                    'level_id'   =>  $val['level_id'],
                    'uid'   =>  $val['uid'],
                    'name'  =>  $val['name'],
                    'nickname'  =>  $val['nickname'],
                    'icon'   =>  \Common\GetCompleteUrl($val['icon'])
                ];
            }


        //等级
        $level_info = $this->modelProfile->selectLevel('id,name');
        $level = [];
        foreach($level_info as $v)
            $level[$v['id']] = $v['name'];


        $exist['teacher_level']= $level[$user[$exist['teacher_uid']]['level_id']];
        $exist['teacher_name'] = $user[$exist['teacher_uid']]['name'];
        $exist['teacher_nickname'] = $user[$exist['teacher_uid']]['nickname'];
        $exist['teacher_uid']  = $exist['new_teacher_uid']?$exist['new_teacher_uid']:'';
        $exist['name']         = $user[$exist['uid']]['name'];
        $exist['nickname']     = $user[$exist['uid']]['nickname'];
        $exist['level']        = $level[$exist['level_id']];
        $exist['new_level']    = $level[$exist['new_level_id']];

        $ret = $exist;


END:
        $this->retReturn($ret);
    }


    /**
     * 申请变更教员列表
     */

    protected function showChangeTeaList($raw,$level,$where){

        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:C('PAGE_LIMIT');
        $limit = $num*($page-1).','.$num;

        $res = $this->model->selectRecordChgTea(
            'a.*,b.o_uid',
            $where,
            $limit,
            'a.id desc'
        );


        if(!$res)
            goto END;
        $uids = [];
        foreach($res as $v){
            $uids[]     = $v['o_uid'];
            $uids[]     = $v['uid'];
            $uids[]     = $v['teacher_uid'];
            $uids[]     = $v['new_teacher_uid'];
        }

        $user = [];
        if($uids)
            $user = \Common\getUserInfo($uids);

        foreach ($res as &$re) {
            $re['type'] = $raw['type'];
            $re['level']    = $level[$re['level_id']];
            $re['ouname']    = $re['o_uid']?$user[$re['o_uid']]['nickname']:'';
            $re['name']     = $user[$re['uid']]['name'];
            $re['nickname'] = $user[$re['uid']]['nickname'];
            $re['teacher_name']     = $user[$re['new_teacher_uid']]['name'];
            $re['teacher_nickname'] = $user[$re['new_teacher_uid']]['nickname'];
//            $re['new_teacher_name'] = $user[$re['new_teacher_uid']]['nickname'];
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
     * 申请变更教员详情
     */

    public function showChangeTeaDetail(){

        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:C('PAGE_LIMIT');
        $limit = $num*($page-1).','.$num;


        $where['id'] = $raw['id'];
        $res = $this->model->findRecordChgTea(
            '',
            $where,
            $limit,
            'id desc'
        );

        if(!$res)
            goto END;

        $uids[]     = $res['uid'];
        $uids[]     = $res['teacher_uid'];
        $uids[]     = $res['new_teacher_uid'];

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

        $res['name'] = $user[$res['uid']]['name'];
        $res['nickname'] = $user[$res['uid']]['nickname'];
        $res['teacher_name'] = $user[$res['teacher_uid']]['nickname'];
        $res['new_teacher_name'] = $user[$res['new_teacher_uid']]['nickname'];

        $ret = $res;
END:

        $this->retReturn($ret);

    }

    /**
     * 为学员升星记录
     */

    protected function showUpgradeList($raw,$level,$where){

        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:C('PAGE_LIMIT');
        $limit = $num*($page-1).','.$num;


        $column = 'a.*,b.o_uid';
        $res = $this->model->selectRecordUpgrade(
            $column,
            $where,
            $limit,
            'a.id desc'
        );

        if(!$res)
            goto END;

        $total = $this->model->selectRecordUpgrade('count(*) total',$where);

        //用户信息
        $uids = [];
        foreach($res as $k=>$v){
            $uids[] = $v['o_uid'];
            $uids[] = $v['uid'];
            $uids[] = $v['teacher_uid'];
        }

        $user = [];
        if($uids)
            $user = \Common\getUserInfo($uids);


        foreach ($res as &$v) {
            $v['type']      = $raw['type'];
            $v['ouname']    = $user[$v['o_uid']]['nickname'];
            $v['name']  = $user[$v['uid']]['name'];
            $v['nickname']  = $user[$v['uid']]['nickname'];
            $v['teacher_name'] = $user[$v['teacher_uid']]['name'];
            $v['teacher_nickname'] = $user[$v['teacher_uid']]['nickname'];
            $v['level']        = $level[$v['level_id']];//申请时的level_id
        }


        $ret['total']  = $total[0]['total'];
        $ret['page_n'] = count($res);
        $ret['page_start'] = $page;
        $ret['data'] = $res;
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
     * 升级教员详情
     */
    public function showUserDetail(){
        $raw = $this->RxData;
        $ret = [];

        if(!$raw['id'])
            goto END;
        $user_info = $this->model->findRecordTea('',['id'=>$raw['id']]);

        if(!$user_info)
            goto END;
        $uid = $user_info['uid'];
        $keys = ['a.*',
            'c.name as level_title,b.name as dname,d.uid as teacher_uid,d.name as teacher_name
            '];


        $res = $this->profile->findUserWith($keys,['a.uid'=>$uid]);
        $up_info = $this->profile->findUpline('a.uid,a.name,a.phone',['b.uid'=>$uid]);


        $rules =  D('AuthRule')->selectUserRoles('b.id,b.title',['a.uid'=>$uid]);

        $ret = $res;
        $roles = [];
        foreach($rules as $v){
            $roles[] = $v['id'];
        }
        $ret['roles'] = $roles;
        $ret['realname'] = $res['name'];

        $ret['teacher']['uid']  = $res['teacher_uid'];
        $ret['teacher']['name'] = $res['teacher_name'];
        unset($ret['teacher_uid']);
        unset($ret['teacher_name']);

        $ret['level']['id'] = $res['level_id'];
        $ret['level']['name'] = $res['level_title'];
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
        $ret['rtime'] = $ret['atime'];
        $ret['icon'] = $ret['icon']?C('PREURL').$ret['icon']:'';
END:
        $this->retReturn( $ret?array_merge($ret,$user_info):$ret );
    }

    /**
     * 学员升星分配教员
     */

    public function editTeacherForUp(){
        $raw = $this->RxData;
        $ret['status'] = E_SYSTEM;
        $keys_m = ['id','check_id','new_teacher_uid'];
        foreach($keys_m as $v) {
            if (!isset($raw[$v]) || $raw[$v] == '') {
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
        }

        $check_info = $this->modelCheck->findCheck('status', ['id'=>$raw['check_id']]);
        if(!$check_info || $check_info['status'] != PASS ){
            $ret['errstr'] = 'not check';
            goto END;
        }

  /*      $diff = time()-$check_info['mtime'];
        if($diff > 60){
            $ret['errstr'] = 'who r u';
            goto END;
        }*/

        $exist = $this->model->findRecordUpgrade('', ['id'=>$raw['id']]);
        if(!$exist){
            $ret['error']  = '不存在的id';
            goto END;
        }
        $teacher_info = $this->modelProfile->findTeacher(
            'id',
            [
                'uid'=>$raw['new_teacher_uid'],
                'quota_left'=>['gt',0],
                'status' => SYS_OK
            ]
        );
        if(!$teacher_info){
            $ret['error']  = 'wrong teacher ';
            goto END;
        }

        $this->model->startTrans();
        $this->modelProfile->startTrans();

        $res1 = $this->model->editRecordUpgrade(['new_teacher_uid'=>$raw['new_teacher_uid']], ['id'=>$raw['id']]);
        $res2 = $this->modelProfile->saveClient(['uid'=>$exist['uid']],['teacher_uid'=>$raw['new_teacher_uid'],'stime'=>time()]);
//教员名额-1
        $res_now = $this->modelProfile->decTeacher(
            ['column'=>'quota_left','value'=>1],
            ['uid'=>$raw['new_teacher_uid']])
        ;

//添加解绑教员记录
        $data_t = [
            [
                'new_teacher_uid'=>$raw['new_teacher_uid'],
                'teacher_uid'=>$exist['teacher_uid'],
                'uid'=>$exist['uid'],
                'atime'=>time(),
                'type'=>TIE,
                'mtime'=>time(),
                'check'=>PASS,
            ]
        ];
        $res_tie = D('Admin/Membership')->addRecordChgTeaAll($data_t);


        if(!$res1 || !$res2){
            $this->model->rollback();
            $this->modelProfile->rollback();
            goto END;
        }

        $this->model->commit();
        $this->modelProfile->commit();
        $ret['status'] = E_OK;
        $ret['error']  = '';

END:
        $this->retReturn($ret);
    }


    public function changeStudyStatusList(){

        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $where = [];
        if($raw['check'])
            $where['a.check']= $raw['check'];
        if($raw['type'])
            $where['a.type']= $raw['type'];
        if($raw['level_id'])
            $where['a.level_id']= $raw['level_id'];

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

        $uids0 = [];

        if($raw['nickname']){
            $user_res1 = D('Client/Profile')->selectClient('uid', ['nickname' => ['like', '%'.$raw['nickname'].'%']]);
            if(!$user_res1)
                goto END;
            foreach ($user_res1 as $item) {
                $uids0[] = $item['uid'];
            }
        }

        if($raw['name']){
            $user_res1 = D('Client/Profile')->selectClient('uid', ['name' => ['like', '%'.$raw['name'].'%']]);

            if(!$user_res1)
                goto END;
            foreach ($user_res1 as $item) {
                $uids0[] = $item['uid'];
            }
        }
        if($uids0)
            $where['a.uid']= ['in',$uids0];

        $page = $raw['page_start'] ? $raw['page_start'] : 1;

        $num  = $raw['page_limit'] ? $raw['page_limit'] : C('PAGE_LIMIT');
        $limit = $num * ($page-1).','.$num;

        $res = $this->model->selectRecordChgStatus('a.*,b.o_uid',$where,$limit,'a.id desc');
        if(!$res)
            goto END;
        $total = $this->model->selectRecordChgStatus('count(*) total',$where,'','a.id desc');

        $uids = [];
        foreach($res as $v){
            $uids[] = $v['o_uid'];
            $uids[] = $v['uid'];
            $uids[] = $v['teacher_uid'];
        }
        $user  = \Common\getUserInfo($uids);
        $level = \Common\getLevelInfo();
        foreach($res as &$v){
            $v['ouname']    = in_array($v['type'],[CHG_BREAK_T,CHG_QUIT_T,CHG_BACK_T])?$user[$v['o_uid']]['nickname']:C_CHECK_SYS;
            $v['name']  = $user[$v['uid']]['name'];
            $v['nickname']  = $user[$v['uid']]['nickname'];
            $v['teacher_name'] = $user[$v['teacher_uid']]['nickname'];
            $v['level']        = $level[$v['level_id']]['name'];//申请时的level
        }

        $ret['page_start'] = $page;
        $ret['total']  = $total[0]['total'];
        $ret['page_n'] = count($res);
        $ret['data']   = $res;


END:
        $this->retReturn( $ret );
    }

    /**
     *申请教员记录详情
     */
    public function showChangeStatusDetail()
    {
        $ret = [];
        $raw = $this->RxData;

        if(!$raw['id'])
            goto END;

        $exist = $this->model->findRecordChgStatus('', ['id'=>$raw['id']]);
        if(!$exist){
            goto END;
        }

        if(!$exist)
            goto END;


        //用户信息
        $level = \Common\getLevelInfo();
        $user_info = $this->modelProfile->findClient('icon,level_id,uid,nickname,name',['uid'=>$exist['uid']]);

        $exist = array_merge($exist,$user_info);

        $exist['level'] = $level[$exist['level_id']]['name'];
        $ret = $exist;



END:
        $this->retReturn($ret);
    }


}
