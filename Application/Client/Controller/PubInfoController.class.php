<?php
namespace Client\Controller;

class PubInfoController extends GlobalController
{
    protected $model;
    protected $modelForum;

    public function _initialize($check = false)
    {
        parent::_initialize($check);
        $this->model      = D('Admin/Resource');
        $this->modelForum = D('Client/Forum');
    }



    /**
     * 帖子评论列表
     */
    public function forumReplyList()
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

        if($page_start != 1)
            $limit = ((($page_start - 1)* $page_limit)-1) . ',' . $page_limit;
        $ret['page_start'] = $page_start;

        $w = [
            'pid' => $id,
            'top_id' => '0',
            'reply_id' => '0'
        ];

        $info = [];
        $total = $this->modelForum->selectReply('count(*) num', $w);

        $id_res = $this->modelForum->selectReply('id', $w, $limit, 'atime asc');
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
        $res = $this->modelForum->selectReply('', $wh,'','id asc');


        $uid = $reply_data = [];
        foreach ($res as $val) {
            $review_id[] = $val['id'];
            if (!in_array($val['uid'], $uid))
                $uid[] = $val['uid'];
        }

        $forum_info = $this->modelForum->findForumOnly('uid',['id'=>$raw['forum_id']]);
        if (isset($res) && !empty($res)) {

            $user_res = D('Profile')->selectClientWithLevel('a.uid,a.icon,a.name,a.nickname,a.roles,b.name as level', ['a.uid' => ['in', $uid]]);
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


    /**
     * 帖子详情
     */
    public function forumDetail(){

        $raw = $this->RxData;
        $ret = [];

        $column = 'a.*,b.icon,b.roles,b.nickname,c.name as level';
        $where['a.status'] = STATUS_ON;
        $where['a.id']  = (int)$raw['id'];
        $res = $this->modelForum->findForum($column,$where);


        if(!$res)
            goto END;
        if($res['check'] != PASS){
            if($res['uid'] != $this->out['uid'])
                goto END;

            $res_check = D('Admin/Check')->findCheckExtOnly(
                '',
                [
                    'pid'   =>$res['check_id'],
                    'table' =>TFORUM,
                    'status'=>1
                ]
            );
            if($res_check['data']){
                foreach(unserialize($res_check['data']) as $k1=>$v1){
                    $res[$k1] = $v1;
                }
            }

        }else{

            $id = $raw['id'];
            $ip = get_client_ip();
            S(['expire'=>C('EXPIRE_CACHE')]);
            if(!S($ip.$id)){
                S($ip.$id,1);
                $this->modelForum->incForum(['field' => 'browse_n', 'value' => 1] ,  ['id'=>$raw['id']]);
            }
        }

        $res['icon'] = $res['icon']?C('PREURL').$res['icon']:'';
        $res['role'] = $res['roles']?explode(',',$res['roles'])[0]:'';

        $ret = $res;
END:

        $this->retReturn($ret);

    }

    /**
     * 帖子列表
     */

    public function forumLists(){

        $raw = $this->RxData;
        $ret = [];
        $head = C('PREURL');

        $page = $raw['page_start']?$raw['page_start']:1;
        $num = $raw['page_limit']? $raw['page_limit']:10;
        $limit = $num*($page-1).','.$num;

        if($page != 1)
            $limit = ($num*($page-1)-1).','.$num;

        $column = 'a.id,a.title,a.content,a.top,a.best,a.atime,a.browse_n,a.reply_n,b.uid,b.icon,b.nickname';
        $where['a.status'] = STATUS_ON;
        $where['a.check']  = PASS;


        if($raw['nickname'])
            $where['b.nickname']= ['like','%'.$raw['nickname'].'%'];

        switch($raw['type']){
            case 1:
                break;
            case 2:
                $where['a.best']= 1;
                break;
            default:
                break;
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

        if($raw['nickname'])
            $where['b.nickname']= ['like','%'.$raw['nickname'].'%'];

        //若要同时匹配标题+内容；打开注释
/*        if($raw['keywords']){
            $b['a.content'] = ['like','%'.$raw['keywords'].'%'];
            $b['a.title']   = ['like','%'.$raw['keywords'].'%'];
            $b['_logic'] = 'or';
            $where[] = $b;47756
        }*/

        if($raw['keywords'])
            $where['a.title'] = ['like','%'.$raw['keywords'].'%'];

        $total = $this->modelForum->selectForum('count(*) total',$where);
        $res = $this->modelForum->selectForum(
            $column,
            $where,
            $limit,
            'a.top asc,a.best asc,a.atime desc'

        );

        if(!$res)
            goto END;
        foreach($res as &$v){
            $v['icon'] = $v['icon']?$head.$v['icon']:'';
        }

        $ret['total'] = $total[0]['total'];
        $ret['page_n'] = count($res);
        $ret['page_start'] = $page;
        $ret['data'] = $res;

END:

        $this->retReturn($ret);

    }




}