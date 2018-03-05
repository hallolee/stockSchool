<?php
namespace Client\Controller;

class ForumController extends GlobalController
{
    protected $model;

    public function _initialize($check = true)
    {

        parent::_initialize($check);
        $this->model = D('Client/Forum');
    }

    public function editForum(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_OK, 'errstr' => '' ];
        $t = time();

        $keys = ['content','title'];

        if($raw['id'] ) {

            $exist = $this->model->findForumOnly('', ['id' => $raw['id']]);
            if($exist['check'] == PASS){
                $ret['status'] = E_STATUS;
                $ret['errstr'] = 'can not change a stable data';
                goto END;
            }

        }
            foreach($keys as $v){
            if(!isset($raw[$v])){
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
            $data[$v] = $raw[$v];
        }

        $data['atime'] = $t;
        $data['uid'] = $this->out['uid'];

        $this->model->startTrans();

        if($raw['id'] && is_numeric($raw['id'])){
            $data_ch = [];
            //最新审核数据
            $res_check = D('Admin/Check')->findCheckExtOnly(
                '',
                [
                    'pid'   =>$exist['check_id'],
                    'table' =>TFORUM,
                    'status'=>1
                ]
            );

            $check_data = unserialize($res_check['data']);
            foreach($data as $k1=>$v1){
                if($check_data[$k1]){
                    if($v1 != $check_data[$k1])
                        $data_ch[$k1] = $v1;
                }else{
                    if($v1 != $exist[$k1])
                        $data_ch[$k1] = $v1;
                }
            }

CHANGE:
            //no data has changed
            if(empty($data_ch)){
                $ret['status'] = E_NOCHANGE;
                $ret['errstr'] = 'no data has changed';
                goto END;
            }

            //do checking
            $check['data'] = [
                'uid' => $this->out['uid'],
                'modify' => MODIFY,
                'atime'  => $t,
                'desc'   => '修改帖子《' . $exist['title'] . '》',
            ];

            $check['data_ext'][] = [
                'tid'   => $raw['id'],
                'table' => TFORUM,
                'data'  => serialize($data_ch),
            ];

            $res_check = D('Client/Check')->editCheckExt(
                ['status'=>2],
                [
                    'tid' => $raw['id'],
                    'status'=>1,
                    'table'=>TFORUM
                ]
            );

            $check_id = A('Check')->addCheck($check, MODIFY);
            $data1['mtime']    = '';
            $data1['reason']   = '';
            $data1['check_id'] = $check_id;
            $data1['check']    = LINE;
            $res = $this->model->editForum($data1, ['id' => $raw['id']]);

            if(!$res_check && !$res){
                $this->model->rollback();
                $ret['status'] = E_SYSTEM;
                $ret['errstr'] = 'edit failed';
                goto END;
            }
        }else{
            //do checking
            $res = $this->model->addForum($data);

            if( !$res){
                $this->model->rollback();
                $ret['status'] = E_STATUS;
                $ret['errstr'] = 'add failed';
                goto END;

            }
            $check = [
                'data_ext' =>
                    [[
                    'tid'   => $res,
                    'table' => TFORUM,
                    'data'  => '',
                ]],
                'data' =>[
                    'uid' => $this->out['uid'],
                    'modify' => ADD,
                    'atime'  => $t,
                    'desc'   => '添加帖子《' . $raw['title'] . '》'
                ]
            ];

            $check_id = A('Client/Check')->addCheck($check, ADD);

            $data1['check_id'] = $check_id;
            $data1['check'] = LINE;

            $res1 = $this->model->editForum($data1,['id'=>$res]);

            if(!$check_id || !$res || !$res1){
                $this->model->rollback();
                $ret['status'] = E_STATUS;
                $ret['errstr'] = 'add failed';
                goto END;

            }

            $ret['id'] = $res;
        }
        $this->model->commit();

END:
        $this->retReturn($ret);

    }

    /**
     * 我发布的帖子
     */

    public function myForumLists(){

        $raw = $this->RxData;
        $ret = [];
        $head = C('PREURL');

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:10;
        $limit = $num*($page-1).','.$num;

        $where['a.uid'] = $this->out['uid'];
        $where['a.status'] = STATUS_ON;

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

        if($raw['keywords']){
            $b['a.content'] = ['like','%'.$raw['keywords'].'%'];
            $b['a.title']   = ['like','%'.$raw['keywords'].'%'];
            $b['_logic'] = 'or';
            $where[] = $b;
        }

        $where['c.table'] = TFORUM;


        $column = '
            a.*,
            c.data
            ';

        $total = $this->model->selectForumWithCheck('count(*) total',$where);
        $res = $this->model->selectForumWithCheck(
            $column,
            $where,
            $limit,
            'a.atime desc ,a.top asc, a.best asc');


        foreach($res as $k=>&$v){
            $v['mtime']   = $v['mtime']?$v['mtime']:'';
            if($v['data']){
                foreach(unserialize($v['data']) as $k1=>$v1){
                    $v[$k1] = $v1;
                }
            }
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


        $realpath = $pre_path . 'forum' . "/" . $file1 . '/uid_' . $uid . '/' . date('Y-m-d', time()) . '/';

        $conf = array(
            'pre'   => '1',
            'types' => [
                'jpg', 'gif', 'png', 'jpeg'
               ]
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
            'path' => $path,
            'name' => $upload_res['file'][0]['savename'],
            'atime' => time(),
        ];
        $res = $this->model->addAttach($data);
        if (!$res)
            goto END;

        $ret['id']       = $res;
        $ret['status']   = E_OK;
        $ret['errstr']   = '';
        $ret['url']  = \Common\GetCompleteUrl($path);
        $ret['path'] = $path;
END:
        $this->retReturn($ret);
    }


    /**
     * 评论
     */
    public function reply()
    {
        $ret = ['status' => E_SYSTEM, 'errstr' => ''];
        $raw = $this->RxData;
        $t = time();


        $keys = ['id', 'reply_id', 'content'];
        foreach ($keys as $val) {
            if (!isset($raw[$val]) || $raw[$val] === '') {
                $ret['status'] = E_DATA;
                goto END;
            }

            $d[$val] = $raw[$val];
        }

        if($raw['img_ids'])
            $d['img_info'] = implode(',',$raw['img_ids']);

        $id = $raw['id'];
        unset($d['id']);
        $d['pid'] = $id;

        $file_exist = $this->model->findForumOnly(
            'id,uid,title,atime,check',
            ['id' => $id]
        );

        if (!$file_exist) {
            $ret['status'] = E_NOEXIST;
            goto END;
        }
        if($file_exist['check'] != PASS){
            $ret['status'] = E_CHECKING;
            goto END;
        }
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
            $d['top_id'] = $d['reply_id'];

        }
        if($raw['img'])
            $d['img_info'] = implode(',',$raw['img']);

        $d['uid'] = $this->out['uid'];
        $d['atime'] = $t;
        $add_reply = $this->model->addReply($d);
        $inc_res = $this->model->incForum(['field'=>'reply_n','value'=>1],['id'=>$id]);

        if($raw['img_ids'])
            $edit_reply_img = $this->model->editAttach(['pid'=>$add_reply],['path'=>['in',$raw['img_ids']]]);

        if(!$add_reply || !$inc_res)
            goto END;

        //通知消息
        $location = $this->model->selectReply('count(*) num', ['pid' => $id,'status'=>SYS_OK]);

        $msg_data = [
            'uid_from'  =>  $this->out['uid'],
            'uid_to'    =>  $file_exist['uid'],
            'reply_id'  =>  $add_reply,
            'type'      =>  M_FORUM,
            'atime'     =>  time(),
            'title'     =>  $file_exist['title'],
            'location'  =>  $location[0]['num'],
        ];
        $res_msg = D('Client/Message')->addMessage($msg_data);
        $res_inc = D('Client/Message')->incMessage(['field'=>'forum_n','value'=>1],['uid'=>$file_exist['uid']]);

        $ret['status'] = E_OK;
        $ret['id']     = $add_reply;
END:
        $this->retReturn($ret);
    }


    /**
     * 帖子评论列表
     */
    public function replyList()
    {
        $raw = $this->RxData;
        $head = C('PREURL');
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];


        if (empty($raw['forum_id']) || !is_numeric($raw['forum_id']))
            goto END;

        $id = $raw['forum_id'];

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
        $res = $this->model->selectReply('', $wh,'','top_id asc,reply_id asc');


        $uid = $reply_data = [];
        foreach ($res as $val) {
            $review_id[] = $val['id'];
            if (!in_array($val['uid'], $uid))
                $uid[] = $val['uid'];
        }


        if (isset($res) && !empty($res)) {

            $user_res = D('Profile')->selectClient('uid,icon,name,nickname', ['uid' => ['in', $uid]]);
            $user = [];
            if ($user_res)
                foreach ($user_res as $val) {
                    $user[$val['uid']] = [
                        'uid'   =>  $val['uid'],
                        'name'  =>  $val['name'],
                        'nickname'  =>  $val['nickname'],
                        'icon'  =>  $val['icon'] ? (stripos($val['icon'], 'http') !== false ? $val['icon'] : $head . $val['icon']) : '',
                    ];

                }


            foreach ($res as $val) {
                $reply_data[$val['id']] = $val;
                $val['nickname'] = $user[$val['uid']]['nickname'];
                $val['name'] = $user[$val['uid']]['name'];
                $val['icon'] = $user[$val['uid']]['icon'];
                if ($val['top_id'] == 0) {
                    if (!$info[$val['id']]) {
                        $info[$val['id']] = $val;
                    }
                }else {
                    $val['to_user'] = [
                        'uid'   => $user[$reply_data[$val['reply_id']]['uid']]['uid'],
                        'name'  => $user[$reply_data[$val['reply_id']]['uid']]['name'],
                    ];

                    $info[$val['top_id']]['reply'][] = $val;
                }
            }

            if ($total)
                $ret['total'] = $total[0]['num'];

            $ret['page_n'] = count($info);
            $ret['data'] = $info;
END:
            $this->retReturn($ret);
        }

    }

    /**
     * 二级评论列表
     */

    public function replySecondList()
    {
        $raw = $this->RxData;
        $head = C('PREURL');
        $ret = [];

        if (empty($raw['id']) || !is_numeric($raw['id']))
            goto END;

        $id = $raw['id'];


        $w = [
            'id' => $id
        ];

        $info = [];
        $id = [$raw['id']];

        $wh = [
            'id' => ['in', $id],
            '_logic' => 'or',
            'top_id' => ['in', $id]
        ];
        $res = $this->model->selectReply('', $wh,'','top_id asc,reply_id asc');


        $uid = $reply_data = [];
        foreach ($res as $val) {
            $review_id[] = $val['id'];
            if (!in_array($val['uid'], $uid))
                $uid[] = $val['uid'];
        }

        if (isset($res) && !empty($res)) {

            $user_res = D('Profile')->selectClient('uid,icon,name,nickname', ['uid' => ['in', $uid]]);
            $user = [];
            if ($user_res)
                foreach ($user_res as $val) {
                    $user[$val['uid']] = [
                        'uid'   =>  $val['uid'],
                        'name'  =>  $val['name'],
                        'nickname'  =>  $val['nickname'],
                        'icon'  =>  $val['icon'] ? (stripos($val['icon'], 'http') !== false ? $val['icon'] : $head . $val['icon']) : '',
                    ];

                }
            foreach ($res as $val) {
                $reply_data[$val['id']] = $val;
                $val['nickname'] = $user[$val['uid']]['nickname'];
                $val['name'] = $user[$val['uid']]['name'];
                $val['icon'] = $user[$val['uid']]['icon'];
                if ($val['top_id'] == 0) {
                    if (!$info[$val['id']]) {
                        $info[$val['id']] = $val;
                    }
                }else {
                    $val['to_user'] = [
                        'uid'   => $user[$reply_data[$val['reply_id']]['uid']]['uid'],
                        'name'  => $user[$reply_data[$val['reply_id']]['uid']]['name'],
                    ];

                    $info[$val['top_id']]['reply'][] = $val;
                }
            }

            foreach($info as $v){
                $ret = $v['reply'];

            }
        }
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


    public function forumDel()
    {
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        if(!$raw['id'])
            goto END;

        $id = $raw['id'][0];

        $where = ['id'=>$id];
        $res = $this->model->findForumOnly('', $where);

        if(!$res){
            $ret = [ 'status' => E_NOEXIST, 'errstr' => '' ];
            goto END;

        }
        $res1 = $this->model->editForum(['status'=>2],$where);

//        if (!$res1)
//            goto END;

        $ret['status'] = E_OK;

END:
        $this->retReturn($ret);
    }

}