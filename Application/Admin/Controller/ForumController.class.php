<?php
namespace Admin\Controller;

class ForumController extends GlobalController
{
    protected $model;
    protected $modelProfile1;

    public function _initialize($check = true)
    {

        parent::_initialize($check);
        $this->model = D('Admin/Forum');
        $this->modelProfile1 = D('Client/Profile');
    }

    public function editForum(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_OK, 'errstr' => '' ];


        $keys = ['content','title'];

        foreach($keys as $v){
            if(!$raw[$v]){
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
            $data[$v] = $raw[$v];
        }
        $data['atime'] = time();
        $data['mtime'] = time();
        $data['check'] = PASS;
        $data['status'] = STATUS_ON;
        $data['uid'] = $this->out['uid'];
        $res = $this->model->addForum($data);

        //check


        if(!$res){
            $ret['status'] = E_STATUS;
            $ret['errstr'] = 'wrong data';
            goto END;

        }

END:
        $this->retReturn($ret);

    }

    public function forumLists(){

        $raw = $this->RxData;
        $ret = [];

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:10;
        $limit = $num*($page-1).','.$num;

        if($page != 1)
            $limit = ((($page - 1)* $num)-1) . ',' . $num;
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

        if($raw['check'])
            $where['a.check'] = $raw['check'];
        if($raw['status'])
            $where['a.status'] = $raw['status'];

        $uids = [];
        if($raw['nickname']){
            $user_info1 = $this->modelProfile1->selectClientLimit('uid',['nickname'=>['like','%'.$raw['nickname'].'%']]);
            if($user_info1){
                foreach($user_info1 as $v){
                    $uids[] = $v['uid'];
                }
                $where['a.uid'] = ['in',$uids];
            }else{
                goto END;
            }
        }
  /*      if($raw['keywords']){
            $b['a.content'] = ['like','%'.$raw['keywords'].'%'];
            $b['_logic'] = 'or';
            $where[] = $b;
        }*/
        $where['a.title']   = ['like','%'.$raw['keywords'].'%'];

        $where['c.table'] = TFORUM;

        $column = '
            a.*,
            b.o_uid,
            c.data
            ';

        $res = $this->model->selectForumWithCheck(
            $column,
            $where,
            $limit,
            'a.top asc, a.best asc,a.atime desc'
        );


        if(!$res)
            goto END;

        $total = $this->model->selectForumWithCheck('count(*) total',$where);

//用户信息
        foreach($res as $v){
            $uids[] = $v['uid'];
            if(!in_array($v['o_uid'],$uids))
                $uids[] = $v['o_uid'];
        }
        if($uids)
            $user = \Common\getUserInfo($uids);


        foreach($res as $k=>&$v){
            if($v['data']){
                foreach(unserialize($v['data']) as $k1=>$v1){
                    $v[$k1] = $v1;
                }
            }
            $v['ouname'] = $v['o_uid']?$user[$v['o_uid']]['nickname']:'';
            $v['nickname'] = $user[$v['uid']]['nickname'];
            $v['name'] = $user[$v['uid']]['name'];
            $v['icon'] = $user[$v['uid']]['icon'];

            unset($v['data']);
        }
        unset($v);

        if(!$res)
            goto END;

        $ret['total'] = $total[0]['total'];
        $ret['page_n'] = count($res);
        $ret['page_start'] = $page;
        $ret['data'] = $res;

END:

        $this->retReturn($ret);

    }


    public function detail(){

        $raw = $this->RxData;
        $ret = [];

        $column = 'a.*,b.icon,b.roles,b.nickname,c.name as level';
        $where['a.id']  = (int)$raw['id'];
        $res = $this->model->findForum($column,$where);
        if(!$res)
            goto END;


        if($res['check_id'] && $res['check'] != PASS){

            $chk_info = D('Admin/Check')->selectCheckWithExt('a.modify,b.data',['a.id'=>$res['check_id']]);
            if($chk_info[0]['modify'] == MODIFY)
                foreach(unserialize($chk_info[0]['data']) as $k=>$v){
                    $res[$k.'_mod'] = $v;
                }
        }
        $res['icon'] = $res['icon']?C('PREURL').$res['icon']:'';
        $res['role'] = $res['roles']?explode(',',$res['roles'])[0]:'';

        $ret = $res;
END:

        $this->retReturn($ret);

    }




    public function setStatus(){
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $raw = $this->RxData;

        if( !in_array($raw['status'], [1,2,3,4,5,6]) ) goto END;
        $task = '';

        $forum_ids = $raw['id'];
        switch ($raw['status']) {
            case '1':
                $d = [ 'status' => 2 ];
                break;

            case '2':
                $d = [ 'status' => 1 ];
                break;

            case '3':
                $d = [ 'best' => 1 ];
                $task = TASK_GET_BEST;
                break;

            case '4':
                $d = [ 'best' => 2 ];
                $task = TASK_GET_BEST_DEL;
                break;

            case '5':
                $d = [ 'top' => 1 ];
                break;

            case '6':
                $d = [ 'top' => 2 ];
                break;

            default:
                $d = [];
                break;
        }

        $res1 = $res2 = true;
        $uid = [];
        if( !empty( $forum_ids ) ){
            $res1 = $this->model->editForum( $d , [ 'id' => [ 'in', $forum_ids ] ]);
        }
        if( $res1 !== false  ){
            $ret['status'] = E_OK;

        }
END:
        $this->retReturn( $ret );
    }


    /**
     * 上传
     */
    public function upload()
    {

        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $uid = $this->out['uid'];
        $head = C('PREURL');
        $raw = $this->RxData;

        $field = 'picture';
        $pre_path = C("UPLOAD_PATH");
        $file1 = '';
        if ($raw['type'] == 1)
            $file1 = 'books';
        if ($raw['type'] == 2)
            $file1 = 'docs';
        if ($raw['type'] == 3)
            $file1 = 'videos';

        $realpath = $pre_path . 'forum' . "/" . $file1 . '/uid_' . $uid . '/' . date('Y-m-d', time()) . '/';

        $conf = array(
            'pre' => '',
            'types' => [
                'jpg', 'gif', 'png', 'jpeg',
                'mp4', 'avi', 'wav',
                'doc', 'ppt', 'pptx',
                'xml', 'txt', 'pdf', 'txt'],
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
        }

        $data = [
            'type' => $raw['type'],
            'path' => $path,
            'atime' => time(),
            'type' => $raw['type']

        ];
        $res = $this->model->addAttach($data);
        if (!$res)
            goto END;

        $ret['id'] = $res;
        $ret['status'] = E_OK;
        $ret['errstr'] = '';
        $ret['file_url'] = $head . $path;
END:
        $this->retReturn($ret);
    }


    /**
     * 评论列表
     */
    public function replyList()
    {
        $raw = $this->RxData;
        $head = C('PREURL');
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];


        if (empty($raw['id']) || !is_numeric($raw['id']))
            goto END;

        $id = $raw['id'];

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

        $id_res = $this->model->selectReply('id', $w, $limit, 'atime DESC');
        if (!$id_res) goto END;

        $id = [];
        foreach ($id_res as $val) {
            $id[] = $val['id'];
        }

        $wh = [
            'id' => ['in', $id],
            '_logic' => 'or',
            'top_id' => ['in', $id]
        ];
        $res = $this->model->selectReply('', $wh,'','id asc');


        $uid = $reply_data = [];
        foreach ($res as $val) {
            $review_id[] = $val['id'];
            if (!in_array($val['uid'], $uid))
                $uid[] = $val['uid'];
        }

        $forum_info = $this->model->findForumOnly('uid',['id'=>$raw['id']]);

        if (isset($res) && !empty($res)) {

            $user_res = D('Client/Profile')->selectClientWithLevel('a.uid,a.icon,a.name,a.nickname,a.roles,b.name as level', ['a.uid' => ['in', $uid]]);
            $user = [];
            if ($user_res)
                foreach ($user_res as $val) {
                    $user[$val['uid']] = [
                        'uid'   =>  $val['uid'],
                        'level' =>  $val['level'],
                        'role'  =>  explode(',',$val['roles'])[0],
                        'name'  =>  $val['name'],
                        'nickname'  =>  $val['nickname'],
                        'icon'  =>  $val['icon'] ? (stripos($val['icon'], 'http') !== false ? $val['icon'] : $head . $val['icon']) : '',
                    ];

                }


            foreach ($res as &$val) {
                $reply_data[$val['id']] = $val;
                $val['nickname'] = $user[$val['uid']]['nickname'];
                $val['name'] = $user[$val['uid']]['nickname'];
                $val['icon'] = $user[$val['uid']]['icon'];
                $val['role'] = $user[$val['uid']]['role'];
                $val['level']= $user[$val['uid']]['level'];
                $val['is_up']= $val['uid'] == $forum_info['uid']?1:0;

                $val['img']  = [];

                if($val['img_info']){
                    $img = explode(',',$val['img_info']);
                    foreach($img as &$v1){
                        $v1 = \Common\GetCompleteUrl($v1);
                    }
                    unset($v1);
                    $val['img']  = $img;
                }
                unset($val['img_info']);


                if ($val['top_id'] == 0) {

                    if (!$info[$val['id']]) {
                        $info[$val['id']] = $val;
                        $info[$val['id']]['reply'] = [];
                    }

                }else {

                    if ($val['place'] == 2)
                        $val['to_user'] = [
                            'uid'   => $user[$reply_data[$val['reply_id']]['uid']]['uid'],
                            'name'  => $user[$reply_data[$val['reply_id']]['uid']]['nickname'],
                        ];

                    $info[$val['top_id']]['reply'][] = $val;
                }
            }
            unset($val);


            if ($total)
                $ret['total'] = $total[0]['num'];

            $ret['page_n'] = count($info);
            $ret['data'] = $info;
END:
            $this->retReturn($ret);
        }


    }


    public function delReview()
    {
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        if(!$raw['id'])
            goto END;

        $where = ['id'=> $raw['review_id']];
        $res = $this->model->findReply('', $where);
        if(!$res){
            $ret = [ 'status' => E_NOEXIST, 'errstr' => '' ];
            goto END;

        }

        $res_del_reply = $this->model->delReply($where);
        $res_dec_count = $this->model->decForum(['field'=>'reply_n','value'=>1],['id'=>$res['pid']]);

        //通知处理
        $message =  D('Client/Message')->findMessage('',['reply_id'=>$raw['review_id']]);
        $res_del_message =  D('Client/Message')->delMessage(['reply_id'=>$raw['review_id']]);

        $res_reset_location = D('Client/Message')->decMessage(
            [
                'field'=>'location',
                'value'=>1
            ],
            ['location'=>['gt',$message['location']],'pid'=>$message['pid']]);

        $ret['status'] = E_OK;

END:
        $this->retReturn($ret);
    }


}