<?php
namespace Client\Controller;

class HomeworkController extends GlobalController{
    protected $model;
    protected $modelProfile;

    public function _initialize($check = true){

        parent::_initialize($check);
        $this->model = D('Client/Homework');
        $this->modelProfile = D('Client/Profile');
    }

    /**
     * homework lists of student
     */

    public function lists(){

        $raw = $this->RxData;
        $head = C('PREURL');
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:10;
        $limit = $num*($page-1).','.$num;


        if(!$this->out['teacher_uid'])
            goto END;
        $time = time();

        $where['a.check']= PASS;
        $where['a.submit']= 1;

        $where['a.uid']  = $this->out['teacher_uid'];
        $where['a.suid'] = ['like','%,'.$this->out['uid'].',%'];


        if($raw['level_id'])
            $where['a.level_id']= $raw['level_id'];
        if(isset($raw['title']))
            $where['a.title']= ['like','%'.$raw['title'].'%'];

        if($raw['status_all'])
            $where['a.atime']= ['lt',$time];

        $raw['status'] = $raw['status_all']?$raw['status_all']:$raw['status_mine'];

        if(isset($raw['status'])){
            switch($raw['status']){
                case 1:
                    $where['b.status']= HWK_DID;
                    break;
                case 2:
                    $where['b.status']= HWK_UNDO;
                    break;
                case 3:
                    $where['b.status']= HWK_UNCOT;
                    break;
                case 4:
                    $where['b.uid']= ['eq',$this->out['uid']];
                    break;
                case 5:
//                    $where['b.uid']= ['neq',$this->out['uid']];
                    break;
                case 6:
//                    $where['b.uid']= ['neq',$this->out['uid']];
                    break;
                default:
                    break;
            }
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

        $column = 'a.id,a.hwk_id,a.title,b.status as stu_status,c.name as level,a.mtime as atime';

        $order = 'a.mtime desc,id desc';


        if(isset($raw['status_mine'])){
            $join = 'RIGHT JOIN';
            $where['b.uid']= $this->out['uid'];

            $total = $this->model->selectHomeworkByRec('count(*) total',$where,'','');
            $res = $this->model->selectHomeworkByRec(
                $column,
                $where,
                $limit,
                $order

            );
        }else {
            //默认为作业池
            if ($raw['status_all'] == 5) {
                $total = $this->model->selectHomeworkForAll('count(*) total', $where, '', '',$this->out['uid']);
                $res = $this->model->selectHomeworkForAll(
                    $column,
                    $where,
                    $limit,
                    $order,$this->out['uid']
                );
            } elseif ($raw['status_all'] == 4) {
                $total = $this->model->selectHomeworkForAll('count(*) total', $where, '', '',$this->out['uid']);
                $res = $this->model->selectHomeworkForAll(
                    $column,
                    $where,
                    $limit,
                    $order,$this->out['uid']
                );
            } else {
                $total = $this->model->selectHomeworkForAll('count(*) total', $where, '', '',$this->out['uid']);
                $res = $this->model->selectHomeworkForAll(
                    $column,
                    $where,
                    $limit,
                    $order,$this->out['uid']
                );
            }
        }

        if(!$res)
            goto END;

        $result = [] ;
        $chk_sub = false;
        foreach($res as $k=>&$v){


            if($v['level_id'] > $this->out['level_id']){
                unset($res[$k]);
                continue;
            }
            $v['etime'] = $v['atime'] + C('EXPIRE_HWK');
            if($raw['status_all'] == 5){
                if($v['stu_status']){
                    continue;
                }
            }
            $v['status'] = $v['stu_status']?$v['stu_status']:HWK_REV;
            if(isset($raw['status_all']) && $v['stu_status']){
                $v['status'] = HWK_NOT_REV;
            }
//过期
            if(!$v['stu_status'] && time() > ($v['atime']+C('EXPIRE_HWK')))
                $v['status'] = HWK_EXIPRE;
//逾期
            if($v['stu_status'] == HWK_UNDO && (time()>($v['atime']+C('EXPIRE_SUB'))))
                $chk_sub = true;

            $result[] = $v;
        }

        $ret['total'] = $total[0]['total'];
        $ret['page_n'] = count($result);
        $ret['page_start'] = $page;
        $ret['data'] = $result;
        $ret['expire'] = $chk_sub?HWK_EXPIRE:HWK_OK;

        //已完成列表 不提示
        if($raw['status'] == 1 )
            $ret['expire'] = HWK_OK;


END:

        $this->retReturn($ret);

    }

    /**
     * homework lists of teacher
     */

    public function tLists(){

        $raw = $this->RxData;
        $head = C('PREURL');
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:10;
        $limit = $num*($page-1).','.$num;

//判断角色
        $roles_arr = [
            ROLE_BIN_TEA,
            ROLE_BIN_TEA+ROLE_BIN_STU,
            ROLE_BIN_TEA+ROLE_BIN_NOR,
            ROLE_BIN_TEA+ROLE_BIN_NOR+ROLE_BIN_STU
        ];
        if(!in_array($this->out['admin'],$roles_arr)){
            $ret['status'] = E_STATUS;
            $ret['errstr']  = '';
            goto END;
        }

        $where['a.uid']  = $this->out['uid'];

        if($raw['level_id'])
            $where['a.level_id']= $raw['level_id'];

        if(isset($raw['title']))
            $where['a.title']= ['like','%'.$raw['title'].'%'];
//        状态:
//        0全部，1待审核，
//2审核通过，3审核拒绝，
//4待批阅；5批阅完成;6、已取消
        if($raw['status']){
            switch($raw['status']){
                case 1:
                    $where['a.check']= LINE;
                    break;
                case 2:
                    $where['a.check'] = PASS;
                    $where['a.submit']= 1;
                    break;
                case 3:
                    $where['a.check']= REJECT;
                    break;
                case 4:
                    $where['a.check']  = PASS;
                    $where['a.submit']  = 1;
                    break;
                case 5:
                    $where['a.judging']= 0;
                    $where['a.check']  = PASS;
                    $where['a.submit']  = 1;
                    break;
                case 6:
                    $where['a.submit'] = 2;
                    $where['a.check']  = PASS;
                    break;
                default:
                    break;
            }
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
        $where['b.table'] = THWK;

        $column = '
            a.id,a.submit,reply_n,a.hwk_id,a.title,a.receive_n,a.judging,a.check status,a.suid,
            c.name as level,a.atime,b.data
        ';

        $res = $this->model->selectHomeworkForT(
            $column,
            $where,
            $limit,
            'a.atime desc'
        );

        if(!$res)
            goto END;
        $total = $this->model->selectHomeworkForT('count(*) total',$where);

        $chk_sub = false;
//逾期
        $pids = [];
        foreach ($res as $v) {
            $pids[] = $v['id'];
        }
        $reply_info = $this->model->selectReply(
            '',
            [
                'pid' => ['in',$pids],
                'top_id' => 0,
                'judge' => HWK_L_UNCOT
            ]
        );
        $t = time();
        foreach($reply_info as $v1){
            if($t>($v1['atime']+C("EXPIRE_SUB")) && $t<($v1['atime']+C("EXPIRE_HWK"))){
                $chk_sub = true;
                continue;
            }
        }

        $ret['expire'] = $chk_sub?HWK_EXPIRE:HWK_OK;
        $fin = [];
        foreach ($res as $k=>&$re) {
            //学员数量
            $stu_n = explode(',',$re['suid']);
            array_shift($stu_n);
            array_pop($stu_n);
            $re['student_n'] = count($stu_n);

            if($re['data']){
                foreach(unserialize($re['data']) as $k=>$v){
                    $re[$k] = $v;
                }
            }
            if($re['status'] == PASS){
                //已取消
                if($re['submit'] == HWK_UNSUMMIT ){
                    $re['status'] = HWK_T_CANCEL;
                    $fin[] = $re;
                    continue;
                }
                if($re['receive_n'] > 0){
                    $re['status'] = HWK_T_UNCOT;
                }
            }else{
                $re['submit'] = HWK_UNSUMMIT;
            }


            if($raw['status'] == HWK_T_UNCOT){
                if($re['reply_n'] == count($stu_n) && $re['judging'] == 0){
                    continue;
                }
            }elseif($raw['status'] == HWK_T_COT){
                if($re['reply_n'] != count($stu_n) ||$re['receive_n'] != count($stu_n) || $re['judging'] != 0){
                    continue;
                }
            }
            //批阅完成
            if($re['reply_n'] == count($stu_n) && $re['judging']  == 0)
                $re['status'] = HWK_T_COT;

            if($raw['status'])
                $re['status'] = $raw['status'];
            unset($re['data']);

            $fin[] = $re;
        }
        $ret['total']  = $total[0]['total'];
        $ret['page_n'] = count($fin);
        $ret['page_start'] = $page;
        $ret['data'] = $fin;

END:
        $this->retReturn($ret);

    }


    /**
     * 新增、修改作业
     */
    public function edit(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_OK, 'errstr' => '' ];
        $t = time();

//判断角色

        $roles_arr = [
            ROLE_BIN_TEA,
            ROLE_BIN_TEA+ROLE_BIN_STU,
            ROLE_BIN_TEA+ROLE_BIN_NOR,
            ROLE_BIN_TEA+ROLE_BIN_NOR+ROLE_BIN_STU
        ];
        if(!in_array($this->out['admin'],$roles_arr)){
            $ret['status'] = E_ROLE;
            $ret['errstr'] = 'no access';
            goto END;
        }

        $keys_m = ['content','title','stock_num','suid'];
        $keys = ['content','title','img','stock_num','level_id','notice'];
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

        //科目
        if($raw['subject']){
            $data['subject'] = serialize($raw['subject']);
            $sub_id = ',';
            foreach ($raw['subject'] as $item) {
                $sub_id .=  $item['id'].',';
            }
            $data['sub_id'] = $sub_id;
        }


        //学员
        $suid = ',';
        foreach ($raw['suid'] as $item) {
            $suid .=  $item.',';
        }

        $data['suid'] = $suid;
        if($raw['id'] ) {
            $exist = $this->model->findHomework('', ['id' => $raw['id']]);
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

            $data_ch  = [];
            foreach($data as $k1=>$v1){
                //对比最新数据与原数据
                if($v1 != $exist[$k1]){
                    $data_ch[$k1] = $v1;
                }
            }

            if(empty($data_ch)){
                $ret['status'] = E_NOCHANGE;
                $ret['errstr'] = 'no data has changed';
                goto END;
            }

        }

        $data['atime'] = $t;
        $data['uid'] = $this->out['uid'];
        $this->model->startTrans();

        if($raw['id'] && is_numeric($raw['id'])){
            //edit

            $check['data'] = [
                'uid' => $this->out['uid'],
                'modify' => MODIFY,
                'atime'  => time()
            ];


            $check['data_ext'][] = [
                'tid'   => $raw['id'],
                'table' => THWK,
                'data'  => serialize($data_ch),
            ];

            $res_check = D('Client/Check')->editCheckExt(
                [
                    'status'=>2],
                    ['tid' => $raw['id'],
                        'status'=>1,
                        'table'=>THWK
                    ]
            );

            $check_id = A('Check')->addCheck($check, 2);
            $data1['ctime'] = time();
            $data1['mtime'] = '';

            $data1['check_id'] = $check_id;
            $data1['mtime'] = null;
            $data1['check'] = LINE;
            $res = $this->model->editHomework($data1, ['id' => $raw['id']]);

            if(!$res){
                $this->model->rollback();
                $ret['status'] = E_SYSTEM;
                $ret['errstr'] = 'edit failed';
                goto END;
            }
        }else{

            $id = $raw['id'];
            $dateStr = date('Y-m-d', time());
            $expire = strtotime($dateStr) + 86400;
            S(['expire'=>($expire-time())]);
            if(!S('sn_hwk')) {
                $id_sn = rand(100,200);
                S('sn_hwk', $id_sn);
            }else{
                $id_sn = S('sn_hwk')+1;
                S('sn_hwk', $id_sn);

            }
            $data['hwk_id'] = date('Ymd').$id_sn;
            $res = $this->model->addHomework($data);
            $ret['id'] = $res;

            if( !$res){
                $this->model->rollback();
                $ret['status'] = E_STATUS;
                $ret['errstr'] = 'add failed';
                goto END;

            }

            $check['data'] = [
                'uid'    => $this->out['uid'],
                'modify' => ADD,
                'atime'  => time(),
                'desc'   => '',
            ];

            $check['data_ext'][] = [
                'tid'   => $res,
                'table' => THWK,
                'data'  => ''

            ];
            $check = A('Client/Check')->addCheck($check, 1);
            if (!$res)
                goto END;
            $data1['check_id'] = $check;
            $res = $this->model->editHomework($data1, ['id' => $res]);

        }

        $this->model->commit();

END:
        $this->retReturn($ret);

    }


    /**
     * 设置作业状态
     */

    public function setStatus(){
        $ret = [ 'status' => E_OK, 'errstr' => '' ];
        $raw = $this->RxData;

        $keys_m = ['id','status'];
        foreach ($keys_m as $val) {
            if (!isset($raw[$val]) || $raw[$val] === '') {
                $ret['status'] = E_DATA;
                goto END;
            }
        }
        if( $raw['status'] != 2 ) goto END;

        $exist = $this->model->findHomework('', ['id' => $raw['id']]);
        if(!$exist){
            $ret['status'] = E_NOEXIST;
            $ret['errstr'] = 'not exists';
            goto END;
        }
        if($exist['submit'] == 2){
            $ret['status'] = E_STATUS;
            $ret['errstr'] = 'already canceled';
            goto END;
        }

        $data1['submit'] = 2;

        $res = $this->model->editHomework($data1, ['id' => $raw['id']]);

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
     * 领取作业
     */

    public function receive(){

        $raw = $this->RxData;
        $ret = [];
        $head = C('PREURL');
        
        if(!$raw['id']){
            $ret['status'] = E_DATA;
            $ret['errstr'] = 'wrong data';
            goto END;
        }


        $exist = $this->model->findHomework('', ['id' => $raw['id']]);
        if(!$exist){
            $ret['status'] = E_NOEXIST;
            $ret['errstr'] = 'not exists';
            goto END;
        }
        //过期
        if(time()>($exist['mtime']+C('EXPIRE_HWK'))){
            $ret['status'] = E_HWK_DEAD_LINE;
            $ret['errstr'] = 'out of dead line';
            goto END;
        }

        //只能领取自己教员发布的作业
        if($this->out['teacher_uid'] != $exist['uid'] || !in_array($this->out['uid'],explode(',',$exist['suid']))){
            $ret['status'] = E_STATUS;
            $ret['errstr'] = 'not your teacher or not for u';
            goto END;
        }

        $exist_rev = $this->model->findHomeworkRecord('', ['pid' => $raw['id'],'uid'=>$this->out['uid']]);
        if($exist_rev){
            $ret['status'] = E_EXIST;
            $ret['errstr'] = 'already has one';
            goto END;
        }

        $data = [
            'pid' => $exist['id'],
            'uid' => $this->out['uid'],
            'rtime' => time()
        ];
        $this->model->startTrans();
        $res = $this->model->addHomeworkRecord($data);

        //领取量+1
        $rec_n = $this->model->incHomework(['field'=>'receive_n','value'=>1],['id'=>$raw['id']]);
        if(!$res || !$rec_n){
            $this->model->rollback();

            $ret['status'] = E_SYSTEM;
            $ret['errstr'] = 'add failed';
            goto END;
        }

        $this->model->commit();

        $ret['status'] = E_OK;
        $ret['errstr'] = '';

END:

        $this->retReturn($ret);

    }


    /**
     * upload
     */
    public function upload(){

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
     * 学员提交作业
     */
    public function reply(){
        $ret = ['status' => E_SYSTEM, 'errstr' => ''];
        $raw = $this->RxData;
        $t = time();


        $keys_m = ['id','img'];
        $keys   = ['content', 'img'];
        $id = $raw['id'];

        $file_exist = $this->model->findHomework('',['id' => $id]);

        if($t>($file_exist['mtime'] + C('EXPIRE_HWK'))){
            $ret['status'] = E_HWK_DEAD_LINE;
            $ret['errstr'] = 'out';
            goto END;
        }

        foreach ($keys_m as $val) {
            if (!isset($raw[$val]) || $raw[$val] === '') {
                $ret['status'] = E_DATA;
                goto END;
            }
        }

        $reply_info = $this->model->findReply(
            '',
            [
                'pid' => $raw['id'],
                'uid' => $this->out['uid']
            ]
        );
        if($reply_info){
            $ret['status'] = E_HWK_SUBMIT;
            $ret['errstr'] = 'already reply';
            goto END;
        }


        foreach ($keys as $val) {
            if (isset($raw[$val]) && $raw[$val] != '')
                $d[$val] = $raw[$val];
        }

        $d['content'] = $raw['content']?$raw['content']:'';

        if($file_exist['check'] != PASS){
            $ret['status'] = E_STATUS;
            $ret['errstr'] = 'wait for check please';
            goto END;
        }
        if (!$file_exist) {
            $ret['status'] = E_NOEXIST;
            goto END;
        }
        $d['pid'] = $id;

        if ($d['reply_id']>0) {
            $chk = $this->model->findReply('id,top_id,place', ['id' => $d['reply_id']]);

            if (isset($chk['top_id'])){
                $d['top_id'] = ($chk['top_id'] > 0) ? $chk['top_id'] : $d['reply_id'];

            }
            $d['place'] = 1;
            if (($chk['place']))
                $d['place'] += $chk['place'];

            if (!$chk) {
                $ret['status'] = E_NOEXIST;
                goto END;
            }
        }else{
            $d['top_id'] = $d['reply_id'] = 0;

        }

        $d['uid'] = $this->out['uid'];
        $d['atime'] = $t;
        if($raw['img']){
            $d['attach'] = serialize($raw['img']);
            $temp =[];
            foreach($raw['img'] as $v){
                $temp[] = $v['path'];
            }
            $d['img_info'] = implode(',',$temp);
        }

        $add_reply = $this->model->addReply($d);
        $this->model->incHomework(['field'=>'reply_n','value'=>1],['id'=>$id]);
        $this->model->incHomework(['field'=>'judging','value'=>1],['id'=>$id]);
        if($raw['img']){
            $edit_reply_img = $this->model->editAttach(['pid'=>$add_reply],['path'=>['in',$temp]]);
        }
        $rec_info = $this->model->findHomeworkRecord('id',['pid'=>$raw['id'],'uid'=>$this->out['uid']]);
        $edit_reply_rec = $this->model->editHomeworkRecord(['status'=>3],['id'=>$rec_info['id']]);


        if(!$add_reply || !$file_exist)
            goto END;


        //通知消息
        $location = $this->model->selectReply('count(*) num', ['pid' => $id,'status'=>SYS_OK]);

        $msg_data = [
            'uid_from'  =>  $this->out['uid'],
            'uid_to'    =>  $file_exist['uid'],
            'reply_id'  =>  $add_reply,
            'pid'       =>  $id,
            'type'      =>  M_HWK,
            'atime'     =>  time(),
            'title'     =>  $file_exist['title'],
            'location'  =>  $location[0]['num'],
        ];
        $res_msg = D('Client/Message')->addMessage($msg_data);
        $res_inc = D('Client/Message')->incMessage(['field'=>'hwk_n','value'=>1],['uid'=>$file_exist['uid']]);

        $ret['status'] = E_OK;
        $ret['id']     = $add_reply;
END:
        $this->retReturn($ret);
    }


    /**
     * 为教员打分
     */

    public function judgeTeacher(){
        $ret = ['status' => E_OK, 'errstr' => ''];
        $raw = $this->RxData;
        $t = time();

        $keys_m = ['id','score'];
        foreach ($keys_m as $val) {
            if (!isset($raw[$val]) || $raw[$val] === '') {
                $ret['status'] = E_DATA;
                goto END;
            }
        }

        $exist_stu = $this->model->findReply(
            '',
            ['pid'=>$raw['id'],'uid'=>$this->out['uid']]
        );
        $exist_tea = $this->model->findReply(
            '',
            ['pid'=>$raw['id'],'reply_id'=>$exist_stu['id']]
        );

        //教员未评阅
        if($exist_stu['judge'] != 1 || !$exist_tea){
            $ret['status'] = E_STATUS;
            $ret['errstr'] = 'waitting please';
            goto END;
        }

        //已为教员打过分
        if($exist_tea['score']){
            $ret['status'] = E_NOEXIST;
            $ret['errstr'] = 'already judged';
            goto END;
        }

        $edit_reply = $this->model->editReply(
            ['judge'=>1,'score'=>$raw['score'],'score_time'=>time()],
            ['id'=>$exist_tea['id']]
        );

        if(!$edit_reply){
            $ret['status'] = E_SYSTEM;
            $ret['errstr'] = 'failed';
            goto END;
        }

END:
        $this->retReturn($ret);

    }


    /**
     * 教员批阅作业
     */
    public function correct(){
        $ret = ['status' => E_SYSTEM, 'errstr' => ''];
        $raw = $this->RxData;
        $t = time();

        $keys_m = ['id','content','score','submit'];
        $keys   = ['content','submit','reply_id'];
        foreach ($keys_m as $val) {
            if (!isset($raw[$val]) || $raw[$val] == '') {
                $ret['status'] = E_DATA;
                goto END;
            }
        }

        if($raw['score'] > 10 || $raw['score'] <0){
            $ret['status'] = E_DATA;
            $ret['errstr'] = 'wrong score';
            goto END;
        }
        $id = $raw['id'];
        $tea_info =  $this->model->findReply('',['reply_id'=>$raw['reply_id'],'pid'=>$raw['id']]);

        if($tea_info['submit'] == 1){
            $ret['status'] = E_STATUS;
            $ret['errstr'] = 'can not change a stable data';
            goto END;
        }

        $file_exist = $this->model->findHomework(
            '',
            ['id' => $id]
        );

        if (!$file_exist) {
            $ret['status'] = E_NOEXIST;
            goto END;
        }
        if($file_exist['check'] != PASS){
            $ret['status'] = E_STATUS;
            $ret['errstr'] = 'wait for check please';
            goto END;
        }

        foreach ($keys as $val) {
            if (isset($raw[$val]) && $raw[$val] != '')
                $d[$val] = $raw[$val];
        }


        $d['pid'] = $id;

        if ($d['reply_id']>0) {
            $chk = $this->model->findReply('id,top_id,place', ['id' => $d['reply_id']]);

            if (isset($chk['top_id']))
                $d['top_id'] = ($chk['top_id'] > 0) ? $chk['top_id'] : $d['reply_id'];
            $d['place'] = 1;
            if (($chk['place']))
                $d['place'] += $chk['place'];

            if (!$chk) {
                $ret['status'] = E_NOEXIST;
                goto END;
            }
        } else {
            $d['top_id'] = $d['reply_id'] = 0;
        }

        if($raw['img']){
            $d['attach'] = serialize($raw['img']);
            $temp =[];
            foreach($raw['img'] as $v){
                $temp[] = $v['path'];
            }
            $d['img_info'] = implode(',',$temp);
        }else{
            $d['attach'] = $d['img_info'] = 0;
        }

        $d['uid'] = $this->out['uid'];
        $d['atime'] = $t;

        $stu_info =  $this->model->findReply('uid',['id'=>$raw['reply_id']]);

        if(!$tea_info){
            $add_reply = $this->model->addReply($d);
            if(!$add_reply )
                goto END;
        }else{
            $add_reply = $this->model->editReply($d,['id'=>$tea_info['id']]);
        }

        if($raw['img'])
            $edit_reply_img = $this->model->editAttach(['pid'=>$add_reply],['path'=>['in',$temp]]);


        $edit_student_reply = $this->model->editReply(
            ['score'=>$raw['score']],['uid' => $stu_info['uid'],'pid'=>$raw['id']]
        );

        //提交
        if($d['submit'] == 1){

            $edit_student_reply = $this->model->editReply(
                ['judge'=>1,'score'=>$raw['score']],['uid' => $stu_info['uid'],'pid'=>$raw['id']]
            );
            $edit_student = $this->model->editHomeworkRecord(['status'=>1],['uid' => $stu_info['uid'],'pid'=>$raw['id']]);
            $this->model->decHomework(['field'=>'judging','value'=>1],['id'=>$id]);

        }


        //通知消息
        $msg_data = [
            'uid_from'  =>  $this->out['uid'],
            'uid_to'    =>  $stu_info['uid'],
            'reply_id'  =>  $add_reply,
            'pid'       =>  $id,
            'type'      =>  M_HWK_TE,
            'atime'     =>  time(),
            'title'     =>  $file_exist['title'],
            'location'  =>  1,
        ];
        $res_msg = D('Client/Message')->addMessage($msg_data);
        $res_inc = D('Client/Message')->incMessage(['field'=>'hwk_n','value'=>1],['uid'=>$stu_info['uid']]);

        $ret['status'] = E_OK;
        $ret['id']     = $add_reply;
END:
        $this->retReturn($ret);
    }


    /**
     * 学员回答列表
     */
    public function replyList(){
        $raw = $this->RxData;
        $head = C('PREURL');
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $keys_m = ['id'];
        foreach($keys_m as $v) {
            if (!isset($raw[$v]) || !is_numeric($raw[$v])) {
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
        }

        $id = $raw['id'];

        $hwk_info = $this->model->findHomeworkOnly('uid', ['id'=>$id]);


        if($this->out['uid'] == $hwk_info['uid'])
            $this->replyListTea($raw);
        $wh_self = [
            'pid' => $id,
            'top_id' => '0',
            'uid'    => $this->out['uid'],
            'reply_id' => '0'
        ];

        $reply_self = $this->model->findReply('id,content,atime,img_info,score,atime,judge', $wh_self);
        //等级
        $star_info = $this->modelProfile->selectLevel('id,name');
        $star = [];
        foreach($star_info as $v)
            $star[$v['id']] = $v['name'];


        if(!$reply_self)
            goto END;

        if($reply_self['img_info'])
            $img_self = $this->getImg($reply_self['img_info']);
        $reply_self['img']  = $img_self?$img_self:[];
        $reply_self['uid']  =  $this->out['uid'];
        $reply_self['icon']   = $this->out['icon'];
        $reply_self['nickname'] = $this->out['nickname'];
        $reply_self['level'] = $this->out['level'];
        $reply_self['status'] = $reply_self['judge'];

        unset($reply_self['img_info']);

        $first = [];
        $first[] = $reply_self;
        $ret = ['total' => 1, 'page_start' => 1, 'page_n' => 1, 'data' => []];

        if($reply_self['judge'] != 1)
            goto NEXT;

        $page_start = !isset($raw['page_start']) ? '1' : $raw['page_start'];
        $page_limit = !isset($raw['page_limit']) ? 10 : $raw['page_limit'];
        $limit = ($page_start - 1) * $page_limit . ',' . $page_limit;
        $ret['page_start'] = $page_start;

        $w = [
            'pid' => $id,
            'top_id' => '0',
            'judge'  => 1,
            'uid'    => ['neq',$this->out['uid']],
            'reply_id' => '0'
        ];

        $info = [];
        $total = $this->model->selectReply('count(*) num', $w);
        $res = $this->model->selectReply('', $w, $limit, 'judge');
        if (!$res){
            goto NEXT;

        }

        $uid = $reply_data = [];
        foreach ($res as $val) {
            $review_id[] = $val['id'];
            $uid[] = $val['uid'];
        }


        if (isset($res) && !empty($res)) {

            $user_res = D('Profile')->selectClient('uid,icon,name,nickname,level_id', ['uid' => ['in', $uid]]);
            $user = [];
            if ($user_res)
                foreach ($user_res as $val) {
                    $user[$val['uid']] = [
                        'uid'   =>  $val['uid'],
                        'name'  =>  $val['name'],
                        'level'  =>  $star[$val['level_id']],
                        'nickname'  =>  $val['nickname'],
                        'icon'   =>  \Common\GetCompleteUrl($val['icon'])
                    ];
                }


            foreach ($res as $k=>$val) {
                if($val['img_info'])
                    $img_temp = $this->getImg($val['img_info']);

                $temp = [
                    'id'   => $val['id'],
                    'status'  => 1,
                    'uid'  => $val['uid'],
                    'content' => $val['content'],
                    'atime'   => $val['atime'],
                    'judge'   => $val['judge'],
                    'score'   => strval($val['score']),
                    'img'     => $img_temp?$img_temp:[],
                ];
                $temp['nickname'] = $user[$val['uid']]['nickname'];
                $temp['name'] = $user[$val['uid']]['name'];
                $temp['icon'] = $user[$val['uid']]['icon'];
                $temp['level']= $user[$val['uid']]['level'];
                $reply_data[$val['id']] = $temp;

            }
            if ($total)
                $ret['total'] += $total[0]['num'];

            $ret['page_n'] = $raw['page_start'] == 1?count($reply_data)+1:count($reply_data);
NEXT:

            $fin = [];
            $fin[] = $first[0];
            if($reply_data)
                foreach($reply_data as $v){
                    $fin[] = $v;
                }
            $ret['data'] = $fin;
END:
            $this->retReturn($ret);
        }

    }


    /**
     *replyList of teacher
     */
    private function replyListTea($raw){
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $keys_m = ['id'];
        foreach($keys_m as $v) {
            if (!isset($raw[$v]) || !is_numeric($raw[$v])) {
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
        }

        $id = $raw['id'];

        //等级
        $star_info = $this->modelProfile->selectLevel('id,name');
        $star = [];
        foreach($star_info as $v)
            $star[$v['id']] = $v['name'];


        $page_start = !isset($raw['page_start']) ? '1' : $raw['page_start'];
        $page_limit = !isset($raw['page_limit']) ? 10 : $raw['page_limit'];
        $limit = ($page_start - 1) * $page_limit . ',' . $page_limit;
        $ret['page_start'] = $page_start;

        $w = [
            'b.pid' => $id];
        $total = $this->model->selectReplyWithRecord('count(*) num', $w);

        $res = $this->model->selectReplyWithRecord('a.*,b.status as sstatus,b.uid as suid',
            $w, $limit, 'a.judge desc,a.atime desc,a.score_time desc');

        $uid = $uid_complete = $reply_data = [];
        foreach ($res as $val) {
            $review_id[] = $val['id'];
            $uid[] = $val['suid'];
        }

        //作业
        $exist = $this->model->findHomework('suid', ['id' => $raw['id']]);
        if(!$exist){
            $ret['status'] = E_NOEXIST;
            $ret['errstr'] = 'not exists';
            goto END;
        }

        //所有学员
        $uid_all = explode(',',$exist['suid']);
        array_shift($uid_all);
        array_pop($uid_all);
        $ret['total'] = count($uid_all);

        $user = \Common\getUserInfo($uid_all);
        if (isset($res) && !empty($res)) {
            foreach ($res as $k=>$val) {
                if($val['img_info'])
                    $img_temp = $this->getImg($val['img_info']);
                if($val['sstatus'] == 2 ){
                    $val['sstatus'] = 3;
                }elseif($val['sstatus'] == 3 ){
                    $val['sstatus'] = 2;
                }
                $temp = [
                    'id'   => $val['id'],
                    'uid'  => $val['suid'],
                    'content' => $val['content'],
                    'atime'   => $val['atime'],
                    'judge'   => $val['judge'],
                    'status'  => $val['sstatus'],
                    'score'   => strval($val['score']),
                    'img'     => $img_temp?$img_temp:[],
                ];
                $temp['nickname'] = $user[$val['suid']]['nickname'];
                $temp['name'] = $user[$val['suid']]['name'];
                $temp['icon'] = $user[$val['suid']]['icon'];
                $temp['level']= $user[$val['suid']]['level'];
                $reply_data[] = $temp;
                unset($temp);

            }
        }
        unset($user);

UNREV:
        //未领取的
        $res_complete = $this->model->selectReplyWithRecord('a.*,b.status as sstatus,b.uid as suid',
            $w, '', 'a.judge desc,a.atime desc,a.score_time desc');

        foreach ($res_complete as $val)
            $uid_complete[] = $val['suid'];

        foreach($uid_all as $k=>$v1){
            if(in_array($v1,$uid_complete))
                unset($uid_all[$k]);
        }
        if(count($res) < $raw['page_limit'] && $uid_all){
                $reply_data_extend = [];
                $user_res = D('Profile')->selectClient('uid,icon,name,nickname,level_id', ['uid' => ['in', $uid_all]]);
                $total= $total[0]['num'];
                if ($user_res){
                    if($res){
                        $start = 0;
                    }else{
                        $start = abs($total - $raw['page_limit']*($raw['page_start']-1)-count($res));
                    }
                    $page_left = $raw['page_limit'] - count($res);
                    $max = $start+$page_left;
                    if(($start+$page_left)>count($user_res))
                        $max = count($user_res);

                    for($i=$start;$i<$max;$i++){
                        $val = $user_res[$i];
                        $user[$val['uid']] = [
                            'uid'   =>  $val['uid'],
                            'name'  =>  $val['name'],
                            'level'  =>  $star[$val['level_id']],
                            'nickname'  =>  $val['nickname'],
                            'icon'   =>  \Common\GetCompleteUrl($val['icon'])
                        ];

                        $temp = [
                            'id'   => '',
                            'uid'  => $val['uid'],
                            'content' => '',
                            'atime'   => $val['atime'],
                            'judge'   => $val['judge'],
                            'status'  => HWK_L_UNREV,
                            'score'   => '',
                            'img' => [],
                        ];
                        $temp['nickname'] = $user[$val['uid']]['nickname'];
                        $temp['name'] = $user[$val['uid']]['name'];
                        $temp['icon'] = $user[$val['uid']]['icon'];
                        $temp['level']= $user[$val['uid']]['level'];
                        $reply_data_extend[] = $temp;
                    }
                }
            }

            $fin = [];
            if($reply_data)
                foreach($reply_data as $v){
                    $fin[] = $v;
                }

            if($fin){
                $ret['data'] = isset($reply_data_extend)?array_merge($fin,$reply_data_extend):$fin;
            }else{
                $ret['data'] = isset($reply_data_extend)?$reply_data_extend:[];
            }
            $ret['page_n'] = count($ret['data']);

END:
            $this->retReturn($ret);

    }

    /**
     * 获取图片完整路径
     * @param $data
     * @return array
     */
    private function getImg($data){
        $img_temp = explode(',',$data);
        $img_self = [];
        foreach($img_temp as $v){
            $img_self[] = \Common\GetCompleteUrl($v);
        }
        return $img_self;

    }

    /**
     * 作业回复详情
     */
    public function showSecondReply(){
        $raw = $this->RxData;
        $head = C('PREURL');
        $ret = $where = [];

        $keys_m = ['reply_id','id'];
        foreach($keys_m as $v) {
            if (!isset($raw[$v]) || !is_numeric($raw[$v])) {
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
        }

        $exist = $this->model->findHomework(
            '',
            ['id' => $raw['id']]
        );

        if (!$exist)    goto END;

        //判断权限
        $uids = explode(',',$exist['suid']);

        if($this->out['uid'] == $exist['uid'])   goto NEXT;

        if(!in_array($this->out['uid'],$uids) )
            goto END;


        $reply_info = $this->model->findReply(
            '',
            ['id' => $raw['reply_id']]
        );
        if($reply_info['uid'] != $this->out['uid']){

            //未被批阅
            if($reply_info['judge'] != 1)
                goto END;

            $reply_info_mine = $this->model->findReply(
                '',
                [
                    'pid' => $reply_info['pid'],
                    'uid'=>$this->out['uid'],
                    'reply_id'=>0
                ]
            );
            if(!$reply_info_mine)
                goto END;
            //自己的未被批阅
            if($reply_info_mine['judge'] != 1)
                goto END;

        }
NEXT:
        $where = [
            'a.id'   => $raw['reply_id'],
            'a.reply_id'  =>0,
            'a.pid'  => $raw['id']
        ];

        $wh = [
            'a.reply_id' => $raw['reply_id'],
            'a.pid'      => $raw['id']
        ];
        $res_stu = $this->model->findReplyWithUid('a.*,b.uid,b.icon,b.nickname', $where);

        $res_tea = $this->model->findReplyWithUid('', $wh);


        $img_stu = $img_tea = [];
        if($res_stu['img_info'])
            foreach(explode(',',$res_stu['img_info']) as $v){
                $img_stu[] = \Common\GetCompleteUrl($v);
            }
        if($res_tea['img_info'])
            foreach(unserialize($res_tea['attach']) as $v){
                $img_tea[] = [
                        'url'  => \Common\GetCompleteUrl($v['path']),
                        'path' => $v['path'],
                        'id'   => $v['id']
                ];
            }


        $ret = [
            'student'=>$res_stu?
                [
                    'reply_id'  => $res_stu['id'],
                    'uid'       => $res_stu['uid'],
                    'icon'      => \Common\GetCompleteUrl($res_stu['icon']),
                    'nickname'  =>  $res_stu['nickname'],
                    'self'      =>  $res_stu['uid']==$this->out['uid']?1:2,
                    'judge'     =>  $res_stu['judge'],
                    'content'   =>  $res_stu['content'],
                    'score'     =>  $res_stu['score']?strval($res_stu['score']):'',
                    'score_time'=>  $res_tea['score_time'],
                    'img'       =>  $img_stu?$img_stu:[],
                    'atime'     =>  $res_stu['atime'],

                ]:[],
            'teacher'=>$res_tea?
                [
                    'reply_id'  => $res_tea['id'],
                    'uid'       => $res_tea['uid'],
                    'icon'      => \Common\GetCompleteUrl($res_tea['icon']),
                    'nickname'  => $res_tea['nickname'],
                    'content'   =>  $res_tea['content'],
                    'judge'     =>  $res_tea['judge'],
                    'score'     =>  $res_tea['score']?strval($res_tea['score']):'',
                    'img'       =>  $img_tea?$img_tea:[],
                    'atime'     =>  $res_tea['atime'],
                    'submit'    =>  $res_tea['submit'],
                    'self'      =>  $this->out['uid']==$exist['uid']?1:2,

                ]:['uid'=>$exist['uid'],'submit'=>2]
        ];


END:
        $this->retReturn($ret);
    }

    /**
     * 作业详情
     *
     */
    public function showDetail(){

        $raw = $this->RxData;
        $ret = [];

        $column = 'a.*,b.icon,b.uid,b.nickname,c.name as level';
        $where['a.status'] = STATUS_ON;
        $where['a.id']  = (int)$raw['id'];
        $res = $this->model->findHomeworkWithUid($column,$where);


        if(!$res)
            goto END;
        if($res['check'] != PASS){
            if($res['uid'] != $this->out['uid'])
                goto END;

            $res_check = D('Admin/Check')->findCheckExtOnly(
                '',
                [
                    'pid'   =>$res['check_id'],
                    'table' =>THWK,
                    'status'=>1
                ]
            );
            if($res_check['data']){
                foreach(unserialize($res_check['data']) as $k1=>$v1){
                    $res[$k1] = $v1;
                }
            }
        }

        $res['finish'] = '';
        if($this->out['uid'] != $res['uid']){
            $exist_rev = $this->model->findHomeworkRecord('status', ['pid' => $raw['id'],'uid'=>$this->out['uid']]);
            $res['finish'] = $exist_rev['status']==2?2:1;

        }

        if($res['subject']){
            $subject = unserialize($res['subject']);
            $tags_temp = $sub_temp = [];
            //科目
            foreach($subject as $k=>$v){
                $res_sub  = $this->model->findSubject('id,name',['id'=>$v['id']]);

                if($v['tags']){
                    foreach($v['tags'] as $v1){
                        $tags_id[] = $v1['id'];
                    }
                    $res_tags  = $this->model->selectTags('id,name',['id'=>['in',$tags_id]]);

                    foreach ( $res_tags as $item) {
                        $tags_temp[] = ['id'=>$item['id'],'name'=>$item['name']];
                    }
                }
                $sub_temp[$k]['id'] = $res_sub['id'] ;
                $sub_temp[$k]['name'] = $res_sub['name'] ;
                $sub_temp[$k]['tags'] = $tags_temp?$tags_temp:[] ;
            }

            unset($tags_temp);

        }

        $stu_n = explode(',',$res['suid']);

        array_shift($stu_n);
        array_pop($stu_n);

        //analysis
        if($this->out['uid'] == $res['uid']){
            $res['receive_per'] = round($res['receive_n']/count($stu_n),2);
            $res['reply_per']   = round($res['reply_n']/$res['receive_n'],2);
            unset($res['suid']);

            if($res['reply_n'] && $res['reply_n'] != $res['judging']){
                $score = $this->model->selectReply('score',['pid'=>$res['id'],'reply_id'=>0,'judge'=>1]);
                $score_tea = $this->model->selectReply('score',['pid'=>$res['id'],'reply_id'=>['gt',0],'judge'=>1]);

                $score_total_tea = $score_total =  0;

                foreach($score as $v1){
                    $score_total += $v1['score'];
                }
                foreach($score_tea as $v2){
                    $score_total_tea += $v2['score'];
                }
                $score_ave = $score_total/count($score);
                $judge_tea = $score_total_tea/count($score_tea);
            }
            //批阅数量
            $res['correct_n'] = isset($score)?count($score):0;
            //学生平均分
            $res['score_ave'] = isset($score_ave)?round($score_ave,2):0;
            //教员平均分
            $res['judge_ave'] = isset($judge_tea)?round($judge_tea,2):0;

            //单个作业成绩=平均分*60%+领取率分*20%+完成率分10%*+满意度分*10%
            $res['score'] = 6*($res['score_ave']/10)+2*$res['receive_per']+$res['reply_per']+$res['judge_ave']/10;

        }
        $res['subject'] = $sub_temp?$sub_temp:[];
        $anas_time = C('ANALYSIS')+C('EXPIRE_HWK')+$res['mtime'];
        if($res['check'] == PASS)
            $res['analysis'] = time()>$anas_time?SYS_OK:SYS_FORBID;

        $res['teacher_uid'] = $res['uid'];
        unset($res['uid']);
        $res['icon'] = \Common\GetCompleteUrl($res['icon']);

        $res['student_n'] = count($stu_n);
        $ret = $res;
END:

        $this->retReturn($ret);

    }


    public function delImg(){
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

    //编辑科目
    public function editSubject(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM ];
        $keys   = ['name','status'];
        $keys_m = ['name'];
        foreach($keys_m as $v) {
            if (!isset($raw[$v]) || !is_numeric($raw[$v])) {
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
        $chg =false;
        if($raw['id']){
            $exist = $this->model->findSubject('id', ['id'=>$raw['id']]);
            if(!$exist){
                $ret['status'] = E_NOEXIST;
                $ret['error']  = '不存在的id';
                goto END;
            }

            foreach($data as $k=>$v){
                if($v != $exist[$k])
                    $chg = true;
            }
            if(!$chg) {
                $ret['status'] = E_NOCHANGE;
                $ret['error']  = 'no change';
                goto END;
            }
            $result = $this->model->editSubject($data, ['id'=>$raw['id']]);

        }else{
            $result = $this->model->addSubject($data);
        }

        if(!$result){
            $ret['status'] = E_SYSTEM;
            $ret['error']  = 'failed';
            goto END;
        }
        $ret['status'] = E_OK;
        $ret['error']  = '';

END:
        $this->retReturn($ret);
    }


    //科目列表
    public function showSubject(){
        $raw = $this->RxData;
        $ret = [];

        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num  = $raw['page_limit'] ? $raw['page_limit'] : C('PAGE_LIMIT');
        $limit = $num * ($page-1).','.$num;

        $result = $this->model->selectSubject('',['status'=>1],$limit,'atime');
        $total = $this->model->selectSubject('count(*) total',['status'=>1],'','atime');

        $ret['data'] = $result;
        $ret['total'] = $total[0]['total'];
        $ret['page_start'] = $page;

END:
        $this->retReturn($ret);
    }


    //编辑标签
    public function editTags(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM ];
        $keys   = ['name','sub_id'];
        $keys_m = ['name','sub_id'];
        foreach($keys_m as $v) {
            if (!isset($raw[$v]) || !is_numeric($raw[$v])) {
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
        }

        foreach ($keys as $v){
            if( isset( $raw[$v] ))
                $data[$v] = $raw[$v];
        }

        $data['atime'] = time();
        $chg =false;
        if($raw['id']){
            $exist = $this->model->findSubject('id', ['id'=>$raw['id']]);
            if(!$exist){
                $ret['status'] = E_NOEXIST;
                $ret['error']  = '不存在的id';
                goto END;
            }

            foreach($data as $k=>$v){
                if($v != $exist[$k])
                    $chg = true;
            }
            if(!$chg) {
                $ret['status'] = E_NOCHANGE;
                $ret['error']  = 'no change';
                goto END;
            }
            $result = $this->model->editTags($data, ['id'=>$raw['id']]);

        }else{
            $result = $this->model->addTags($data);
        }

        if(!$result){
            $ret['status'] = E_SYSTEM;
            $ret['error']  = 'failed';
            goto END;
        }
        $ret['status'] = E_OK;
        $ret['error']  = '';

END:
        $this->retReturn($ret);
    }


    //标签列表
    public function showTags(){
        $raw = $this->RxData;
        $ret = [];


        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num  = $raw['page_limit'] ? $raw['page_limit'] : C('PAGE_LIMIT');
        $limit = $num * ($page-1).','.$num;

        $keys_m   = ['sub_id'];
        foreach($keys_m as $v) {
            if (!isset($raw[$v]) || !is_numeric($raw[$v])) {
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
        }

        $result = $this->model->selectTags('',['sub_id'=>$raw['sub_id']],$limit,'id desc');
        $total = $this->model->selectTags('count(*) total',['sub_id'=>$raw['sub_id']],'','');

        $ret['data'] = $result;
        $ret['total'] = $total[0]['total'];
        $ret['page_start'] = $page;

END:
        $this->retReturn($ret);
    }


    //我的学员列表
    public function myStudents(){
        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num  = $raw['page_limit'] ? $raw['page_limit'] : C('PAGE_LIMIT');
        $limit = $num * ($page-1).','.$num;

//判断角色
        $roles_arr = [
            ROLE_BIN_TEA,
            ROLE_BIN_TEA+ROLE_BIN_STU,
            ROLE_BIN_TEA+ROLE_BIN_NOR,
            ROLE_BIN_TEA+ROLE_BIN_NOR+ROLE_BIN_STU
        ];
        if(!in_array($this->out['admin'],$roles_arr))
            goto END;

        if($raw['level_id']){
            $where['level_id'] = $raw['level_id'];

        }elseif($raw['level_id_least']){
            $where['level_id'] = ['egt',$raw['level_id_least']];
        }
        if($raw['status_stu'])
            $where['status_stu'] = $raw['status_stu'];

        if ($raw['nickname'])
            $where['nickname'] = ['like','%'.$raw['nickname'].'%'];
        if ($raw['xiaozhu'])
            $where['xiaozhu'] = ['like','%'.$raw['xiaozhu'].'%'];

        if($raw['time_start'] && $raw['time_end']){
            $where['stime'] = [
                ['egt',$raw['time_start']],
                ['lt',$raw['time_end']]
            ];
        }elseif($raw['time_start'] ) {
            $where['stime'] = ['egt', $raw['time_start']];
        }elseif($raw['time_end'] ){
            $where['stime'] = ['lt',$raw['time_end']];
        }


        $where['teacher_uid'] = $this->out['uid'];
        $result = $this->modelProfile->selectClientLimit(
            'uid,xiaozhu,stime,nickname,icon,phone,name,level_id,status_stu as status',
            $where,
            $limit,
            'stime desc');

        if(!$result)
            goto END;

        //等级
        $level = \Common\getLevelInfo();

        $w = ['check'=>['neq',REJECT]];

        $upgrade_info = D('Membership')->selectRecordUpgrade(
           'a.uid,a.check,b.nickname',
           $w,
           '',
           'a.id desc'
       );

        $upgrade = [];
        foreach($upgrade_info as $v){
            $upgrade[$v['uid']] = $v['check'];
        }

        foreach($result as &$v){
            $v['icon'] = \Common\GetCompleteUrl($v['icon']);
            $v['level'] = $level[$v['level_id']]['name'];
            $v['upgrade'] = $upgrade[$v['uid']]?1:2;

            $recevie_data = $this->model->selectHomeworkRecord('count(*) total',['uid'=>$v['uid']]);
            $reply_data   = $this->model->selectHomeworkRecord('count(*) total',['uid'=>$v['uid'],'status'=>['neq',HWK_UNDO]]);
            $score_data   = $this->model->selectReply('score',['uid'=>$v['uid'],'judge'=>SYS_OK]);
            $score = 0;
            if($score_data)
                foreach ($score_data as $item) {
                    $score+= $item['score'];
                }
            $v['score_ave']   = round($score/count($score_data),2);
            $v['reply_n']   = $reply_data[0]['total'];
            $v['receive_n'] = $recevie_data[0]['total'];
            $v['reply_per'] = round($v['reply_n']/$v['receive_n'],2);
            $v['level'] = $level[$v['level_id']]['name'];

        }

        $total = $this->modelProfile->selectClientLimit(
            'count(*) total',
            ['teacher_uid'=>$this->out['uid']],
             '',
            'atime desc');

        $ret['total']  = $total[0]['total'];
        $ret['page_n'] = count($result);
        $ret['page_start'] = $page;
        $ret['data'] = $result;

END:

        $this->retReturn($ret);
    }


    public function levelList(){
        $ret = [];
        $raw = $this->RxData;
        $where['status'] = ['neq',SYS_FORBID];
        if($raw['homework'])
            $where['id'] = ['elt',$this->out['level_id']];
        $res = $this->modelProfile->selectLevel('',$where);
        if( $res )
            $ret = $res;

        $this->retReturn( $ret );
    }




}