<?php
namespace Admin\Controller;

class HomeworkController extends GlobalController
{
    protected $model;
    protected $model1;
    protected $modelSystem;
    protected $modelProfile;
    protected $modelProfile1;

    public function _initialize($check = true)
    {

        parent::_initialize($check);
        $this->model = D('Admin/Homework');
        $this->model1 = D('Client/Homework');
        $this->modelProfile = D('Admin/Profile');
        $this->modelProfile1 = D('Client/Profile');
        $this->modelSystem = D('Admin/System');
    }

    /**
     * homework lists of student
     */

    public function lists(){

        $raw = $this->RxData;
        $ret = [];
        $head = C('PREURL');

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:10;
        $limit = $num*($page-1).','.$num;


        if($raw['level_id'])
            $where['a.level_id']= $raw['level_id'];
        if($raw['status'])
            $where['a.status']= $raw['status'];
        if(isset($raw['title']))
            $where['a.title']= ['like','%'.$raw['title'].'%'];

        if($raw['check']){
            $where['a.submit'] = HWK_SUB;
            switch($raw['check']){
                case 1:
                    $where['a.check']= LINE;
                    break;
                case 2:
                    $where['a.check']= PASS;
                    break;
                case 3:
                    $where['a.check']= REJECT;
                    break;
                case 6:
                    $where['a.check']= PASS;
                    $where['a.submit']= ['neq',HWK_SUB];
                    break;
                default:
                    break;
            }
        }

        $uids = [];
        if($raw['teacher_name']){
            $user_info1 = $this->modelProfile1->selectClientLimit('uid',['nickname'=>['like','%'.$raw['teacher_name'].'%']]);
            if(!$user_info1)
                goto END;

            foreach($user_info1 as $v){
                $uids[] = $v['uid'];
                $uids[] = $v['teacher_uid'];
            }

            $where['a.uid'] = ['in',$uids];

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
        $where['d.table'] = THWK;

        $column = 'a.*,c.o_uid,d.data';

        $res = $this->model->selectHomework(
            $column,
            $where,
            $limit,
            'a.atime desc'
        );

        if(!$res)
            goto END;

        $total = $this->model->selectHomework('count(*) total',$where);

//等级
        $level_info = $this->modelProfile1->selectLevel('id,name');
        $level = [];
        foreach($level_info as $v)
            $level[$v['id']] = $v['name'];

//用户信息
        foreach($res as $v){
            if($v['o_uid'])
                $uids[] = $v['o_uid'];
            $uids[] = $v['uid'];
        }

        if($uids){
            $user_info = $this->modelProfile1->selectClient('uid,nickname,name',['uid'=>['in',$uids]]);
            $user = [];
            if($user_info)
                foreach ($user_info as $val) {
                    $user[$val['uid']] = [
                        'uid'   =>  $val['uid'],
                        'name'  =>  $val['name'],
                        'nickname'  =>  $val['nickname'],
                    ];
                }
        }


        foreach($res as &$v){
            $v['icon']   = \Common\GetCompleteUrl($v['icon']);
            $v['level']  = $level[$v['level_id']];
            $v['ouname'] = $v['o_uid']?$user[$v['o_uid']]['nickname']:'';
            $v['teacher_name'] = $user[$v['uid']]['name'];
            $v['teacher_nickname'] = $user[$v['uid']]['nickname'];
            if($v['data']){
                foreach(unserialize($v['data']) as $k=>$v1){
                    $v[$k] = $v1;
                }
            }
            if($v['check'] == PASS && $v['submit'] == 2)
                $v['check'] = HWK_T_CANCEL;
        }

        $ret['total'] = $total[0]['total'];
        $ret['page_n'] = count($res);
        $ret['page_start'] = $page;
        $ret['data'] = $res;

END:

        $this->retReturn($ret);
    }


    /**
     * 学员回答列表
     */
    public function replyList()
    {
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
            'pid' => $id,
            'top_id' => '0',
            'reply_id' => '0'
        ];
        $info = [];
        $total = $this->model->selectReply('count(*) num', $w);

        $res = $this->model->selectReply('', $w, $limit, 'atime DESC');

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
                    'uid'  => $val['uid'],
                    'content' => $val['content'],
                    'atime'   => $val['atime'],
                    'score'   => $val['score'],
                    'img' => $img_temp?$img_temp:[],
                ];
                $temp['nickname'] = $user[$val['uid']]['nickname'];
                $temp['name'] = $user[$val['uid']]['name'];
                $temp['icon'] = $user[$val['uid']]['icon'];
                $temp['level']= $user[$val['uid']]['level'];
                $reply_data[$val['id']] = $temp;

            }
            if ($total)
                $ret['total'] = $total[0]['num'];

            $fin = [];
            if($reply_data)
                foreach($reply_data as $v){
                    $fin[] = $v;
                }
            $ret['page_n'] = count($fin);
            $ret['data'] = $fin;
END:
            $this->retReturn($ret);
        }

    }


    /**
     * 作业回复详情
     */

    public function showSecondReply()
    {
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
            $where[$v] = $raw[$v];
        }


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
            foreach(explode(',',$res_tea['img_info']) as $v){
                $img_tea[] = \Common\GetCompleteUrl($v);
            }


        $ret = [
            'student'=>$res_stu?
                [
                    'uid'       => $res_stu['uid'],
                    'icon'      => \Common\GetCompleteUrl($res_stu['icon']),
                    'nickname'  =>  $res_stu['nickname'],
                    'self'      =>  $res_stu['uid']==$this->out['uid']?1:2,
                    'content'   =>  $res_stu['content'],
                    'score'     =>  $res_tea['score'],
                    'score_time'=>  $res_tea['score_time'],
                    'img'       =>  $img_stu?$img_stu:[],
                ]:[],
            'teacher'=>$res_tea?
                [
                    'uid'       => $res_tea['uid'],
                    'icon'      => \Common\GetCompleteUrl($res_tea['icon']),
                    'nickname'  => $res_tea['nickname'],
                    'content'   =>  $res_tea['content'],
                    'score'     =>  $res_stu['score'],
                    'img'       =>  $img_tea?$img_tea:[],
                ]:[]
        ];


END:
        $this->retReturn($ret);
    }

    /**
     * 作业详情
     */
    public function showDetail(){

        $raw = $this->RxData;
        $ret = [];

        $column = 'a.*,b.icon,b.uid,b.name as teacher_name,b.nickname as teacher_nickname,c.name as level';
        $where['a.id']  = (int)$raw['id'];
        $res = $this->model->findHomeworkWithUid($column,$where);

        if(!$res)
            goto END;
        if($res['check'] != PASS){
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
                    $res[$k1.'_mod'] = $v1;
                }
            }
        }
        //科目
        if($res['subject']){
            $sub_temp = $this->getSub($res['subject']);
            $res['subject'] = $sub_temp;
        }

        //变更科目
        if(isset($res['subject_mod'])){
            $subject = $res['subject_mod'];
            $sub_temp = $this->getSub($subject);
            $res['subject_mod'] = $sub_temp;
        }
        $stu_n = explode(',',$res['suid']);

        array_shift($stu_n);
        array_pop($stu_n);

        $res['student_n'] = count($stu_n);

        $res['icon'] = \Common\GetCompleteUrl($res['icon']);

        $ret = $res?$res:[];
END:

        $this->retReturn($ret);

    }

    private function getSub($subject){
            $subject = unserialize($subject);
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
                $sub_temp[$k]['tags'] = $tags_temp?$tags_temp:[];

            unset($tags_temp);

        }
        return $sub_temp;
    }

    //编辑科目
    public function editSubject()
    {
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM ];
        $keys   = ['name','status'];
        $keys_m = ['name'];
        foreach($keys_m as $v) {
            if (!isset($raw[$v]) || $raw[$v] == '') {
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



    //删除科目
    public function delSubject()
    {
        $raw = $this->RxData;
        $ret = [ 'status' => E_OK ];
        $keys_m = ['id'];
        foreach($keys_m as $v) {
            if (!isset($raw[$v]) || $raw[$v] == '') {
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
        }

        $exist = $this->model->findSubject('id', ['id'=>$raw['id']]);
        if(!$exist){

            $ret['status'] = E_NOEXIST;
            $ret['error']  = '不存在的id';
            goto END;
        }
        $exist_hwk = $this->model->findHomeworkOnly('id', ['sub_id'=>['like','%,'.$raw['id'].',%']]);
        if($exist_hwk){
            $ret['status'] = E_EXIST;
            $ret['error']  = '';
            goto END;
        }

        $res = $this->model->delSubject(['id'=>$raw['id']]);
        if(!$res){
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
    public function showSubject()
    {
        $raw = $this->RxData;
        $ret = [];


        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num  = $raw['page_limit'] ? $raw['page_limit'] : C('PAGE_LIMIT');
        $limit = $num * ($page-1).','.$num;

        $where = [];
        if(isset($raw['name']))
            $where['name'] = ['like','%'.$raw['name'].'%'];
        if($raw['status'])
            $where['status'] = $raw['status'];
        $result = $this->model->selectSubject('',$where,$limit,'id desc');
        $total = $this->model->selectSubject('count(*) total','','');

        $ret['total'] = $total[0]['total'];
        $ret['page_start'] = $page;
        $ret['page_n'] = count($result);
        $ret['data'] = $result;



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
            if (!isset($raw[$v]) || $raw[$v] == '') {
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
            $exist = $this->model->findTags('id', ['id'=>$raw['id']]);
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


    //删除标签
    public function delTags(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_OK ];
        $keys_m = ['id'];
        foreach($keys_m as $v) {
            if (!isset($raw[$v]) || $raw[$v] == '') {
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
        }

        $exist = $this->model->findTags('id', ['id'=>$raw['id']]);
        if(!$exist){
            $ret['status'] = E_NOEXIST;
            $ret['error']  = '不存在的id';
            goto END;
        }

        $res = $this->model->delTags(['id'=>$raw['id']]);
        if(!$res){
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
    public function showTags()
    {
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


        $where['sub_id'] = $raw['sub_id'];
        if(isset($raw['name']))
            $where['name'] = ['like','%'.$raw['name'].'%'];
        if($raw['status'])
            $where['status'] = $raw['status'];

        $result = $this->model->selectTags('',$where,$limit,'id desc');
        $total = $this->model->selectTags('count(*) total',$where,'');

        $ret['data'] = $result;
        $ret['total'] = $total[0]['total'];
        $ret['page_start'] = $page;

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
     * 作业统计
     */
    public function analysisLists(){

        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:10;
        $limit = $num*($page-1).','.$num;


        if($raw['level_id'])
            $where['a.level_id']= $raw['level_id'];

        $where['a.status'] = 1;
        $where['a.submit'] = 1;
        $where['a.check']  = PASS;


        if(isset($raw['title']))
            $where['a.title']= ['like','%'.$raw['title'].'%'];

        if(isset($raw['teacher_name']))
            $where['b.name']= ['like','%'.$raw['teacher_name'].'%'];

        if(isset($raw['teacher_nickname']))
            $where['b.nickname']= ['like','%'.$raw['teacher_nickname'].'%'];

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

        //作业等级
        $level_info = \Common\getLevelInfo();

        $column = 'a.id,a.title,a.level_id,a.reply_n,a.receive_n,a.suid,
        b.nickname as teacher_nickname,b.name as teacher_name,b.icon,b.uid,a.mtime as atime,status_te';

        $res = $this->model->selectHomeworkForAnalysis(
            $column,
            $where,
            $limit,
            'a.atime desc'
        );

        if(!$res)
            goto END;
        $total = $this->model->selectHomeworkForAnalysis('count(*) total',$where);
        foreach($res as &$v){

            $v['icon'] = \Common\GetCompleteUrl($v['icon']);
            $v['level'] = $level_info[$v['level_id']]['name'];
            $stu_n = explode(',',$v['suid']);

            array_shift($stu_n);
            array_pop($stu_n);
            if($v['data']){
                foreach(unserialize($v['data']) as $k=>$v1){
                    $v[$k] = $v1;
                }
            }
            $v['student_n'] = count($stu_n);

            $v['receive_per'] = round($v['receive_n']/count($stu_n),2);
            $v['reply_per'] = round($v['reply_n']/$v['receive_n'],2);
            unset($v['suid']);

            if($v['reply_n'] && $v['reply_n'] != $v['judging']){
                $score = $this->model->selectReply('score',['pid'=>$v['id'],'reply_id'=>0,'judge'=>1]);
                $score_tea = $this->model->selectReply('score',['pid'=>$v['id'],'reply_id'=>['gt',0],'judge'=>1]);

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
            $v['correct_n'] = isset($score)?count($score):0;
            //学生平均分
            $v['score_ave'] = isset($score_ave)?round($score_ave,2):0;
            //教员平均分
            $v['judge_ave'] = isset($judge_tea)?round($judge_tea,2):0;

            //单个作业成绩=平均分*60%+领取率分*20%+完成率分10%*+满意度分*10%
            $v['score'] = round(6*($v['score_ave']/10)+2*$v['receive_per']+$v['reply_per']+$v['judge_ave']/10,2);

        }

        $ret['total']  = $total[0]['total'];
        $ret['page_n'] = count($res);
        $ret['page_start'] = $page;
        $ret['data'] = $res;

END:

        $this->retReturn($ret);

    }


    /**
     * 学员作业统计
     */

    public function analysiStu(){
        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num  = $raw['page_limit'] ? $raw['page_limit'] : C('PAGE_LIMIT');
        $limit = $num * ($page-1).','.$num;

        //全部学员
        $user_info = $this->modelSystem->selectUserRoleGroup('a.uid',['a.group_id'=>ROLE_DB_STU]);
        if(!$user_info)
            goto END;
        $uid_stu = [];

        foreach($user_info as $v)
            $uid_stu[] = $v['uid'];

        $where['uid'] = ['in',$uid_stu];
        if($raw['level_id'])
            $where['level_id']= $raw['level_id'];

        if ($raw['teacher_nickname'])
            $where['nickname'] = ['like','%'.$raw['teacher_nickname'].'%'];
        if ($raw['teacher_name'])
            $where['name'] = ['like','%'.$raw['teacher_name'].'%'];
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
        $result = $this->modelProfile1->selectClientLimit(
            'uid,xiaozhu,stime as atime,nickname,icon,phone,name,level_id,stime,status_stu',
            $where,
            $limit,
            'stime desc');

        if(!$result)
            goto END;

        //等级
        $level = \Common\getLevelInfo();

        foreach($result as &$v){
            $recevie_data = $this->model1->selectHomeworkRecord('count(*) total',['uid'=>$v['uid']]);
            $reply_data   = $this->model1->selectHomeworkRecord('count(*) total',['uid'=>$v['uid'],'status'=>['neq',HWK_UNDO]]);
            $score_data   = $this->model1->selectReply('score',['uid'=>$v['uid'],'judge'=>SYS_OK]);
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

        $total = $this->modelProfile1->selectClientLimit(
            'count(*) total',
            ['teacher_uid'=>$this->out['uid']],
            '')
        ;

        $ret['total']  = $total[0]['total'];
        $ret['page_n'] = count($result);
        $ret['page_start'] = $page;
        $ret['data'] = $result;

END:

        $this->retReturn($ret);
    }


}