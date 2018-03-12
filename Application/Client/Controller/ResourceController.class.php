<?php
namespace Client\Controller;

class ResourceController extends GlobalController
{
    protected $model;
    protected $modelCheck;

    public function _initialize($check = true)
    {
        parent::_initialize($check);
        $this->model      = D('Admin/Resource');
        $this->modelCheck = D('Admin/Check');
    }


    /**
     * 上传
     */
    public function upload()
    {

        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $uid = $this->out['uid'];
        $raw = $this->RxData;

        $field = 'picture';
        $pre_path = C("UPLOAD_PATH");

        $check_type = explode('.',$raw['name']);

        $file1 = 'pics';
        switch($raw['type'] ){
            case 1:
                $file1 = 'books';
                $types = [
                    'jpg', 'gif', 'png', 'jpeg','png',
                    'xml', 'txt', 'pdf', 'text'
                ];
                if(!in_array(end($check_type),$types)){
                    $ret['status'] = E_FILE_TYPE;
                    $ret['errstr'] = 'wrong type1';
                    goto END;
                  }

                break;

            case 2:
                $file1 = 'docs';

                $types = [
                    'jpg', 'gif', 'png', 'jpeg','png','xlsx','xls',
                    'doc', 'ppt', 'pptx','docx','xml','txt'
                ];
                if(!in_array(end($check_type),$types)){
                $ret['status'] = E_FILE_TYPE;
                $ret['errstr'] = 'wrong type3';
                goto END;
            }
                break;

            case 3:
                $file1 = 'videos';

                $types = [
                       'mp4', 'avi', 'wmv'
                   ];
                if(!in_array(end($check_type),$types)){
                $ret['status'] = E_FILE_TYPE;
                $ret['errstr'] = 'wrong type';
                goto END;
            }
                break;

            case 4:
                $file1 = 'pics';

                $types = [
                    'jpg','gif','png','jpeg'
                ];

                if(!in_array(end($check_type),$types))
                {
                    $ret['status'] = E_FILE_TYPE;
                    $ret['errstr'] = 'wrong type';
                    goto END;
                }
                break;

        }

        $realpath = $pre_path . 'resource' . "/" . $file1 . '/uid_' . $uid . '/' . date('Y-m-d', time()) . '/';

        $conf = array(
            'pre'      => '',
            'savename' => array_slice($check_type,-2,1)[0].date('i-s',time()),
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
            'type'  => $raw['type'],
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
        $ret['file_url'] = \Common\GetCompleteUrl($path);
        $ret['path']     =  $path;
END:
        $this->retReturn($ret);
    }


    /**
     * 新增/编辑资源
     */

    public function edit()
    {
        if(!$this->out['uid']){
            $ret['status'] = E_TOKEN;
            goto END;
        }
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        $keys = [
            'title', 'type', 'desc', 'author', 'img_path',
            'attach_ids', 'content', 'is_top', 'status'
        ];
        $keys_m = [
            'title','content'
        ];

        foreach ($keys_m as $v) {
            if (!isset($raw[$v]) || empty($raw[$v]) ){
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong data';
                goto END;
            }
        }

        $data = [];
        foreach ($keys as $v) {
            if (isset($raw[$v]) && !empty($raw[$v]))
                $data[$v] = $raw[$v];
        }
        if($raw['img_path']){
            //进行图片压缩
            $file_path = explode('/',$raw['img_path']);
            $thumb = \Common\_Thumb($raw['img_path'],end($file_path));
            if( $thumb['status'] != 0 ){
                $ret =  $thumb;
                goto END;
            }
            array_pop($file_path);
            //图片路径
            $file_path = implode('/',$file_path);
            //压缩后的图片路径
            $thumbpath = $file_path.'/'.$thumb['savename'];
            $data['img_path_thumb'] = $thumbpath;

        }

        if (empty($data) || !in_array($raw['type'], [1, 2, 3])) 
            goto END;

        if (is_numeric($raw['id'])) {
            $id = $raw['id'];
            $exist = $this->model->findFile('', ['id' => $raw['id']]);
            if (!$exist) {
                $ret['status'] = E_NOEXIST;
                goto END;
            }

            $data_ch  = [];
            foreach($data as $k1=>$v1){
                //对比最新数据与原数据
                if($v1 != $exist[$k1])
                    $data_ch[$k1] = $v1;
            }

            if(empty($data_ch)){
                $ret['status'] = E_NOCHANGE;
                goto END;
            }

            //最新审核数据
            $res_check = D('Admin/Check')->findCheckExtOnly(
                '',
                [
                    'pid'   =>$exist['check_id'],
                    'table' =>TFILE,
                    'status'=>1
                ]
            );

            if($res_check){
                //判断与最近一次修改是否有差异
                if(serialize($data_ch) == $res_check['data']){
                    $ret['status'] = E_NOCHANGE;
                    goto END;
                }
            }

            $check['data'] = [
                'uid' => $this->out['uid'],
                'modify' => MODIFY,
                'once'   => 2,
                'atime'  => time(),
                'desc'   => '',
            ];


            //图片处理
            if(!$raw['img_path'] && $exist['check'] != PASS){
                $last_check = D('Client/Check')->findChkExt('', ['tid' => $raw['id'],'status'=>1]);
                $last_img = unserialize($last_check['data']);
                if($last_img['img_path'])
                    $data_ch['img_path'] = $last_img['img_path'];
            }

            $check['data_ext'][] = [
                'tid'   => $raw['id'],
                'table' => TFILE,
                'data'  => serialize($data_ch),
            ];

            $res_check = D('Client/Check')->editCheckExt(['status'=>2], ['tid' => $raw['id'],'status'=>1,'table'=>TFILE]);

            $check_id = A('Check')->addCheck($check, 2);
            $data1['ctime'] = time();
            $data1['mtime'] = '';

            $data1['check_id'] = $check_id;
            $data1['check'] = LINE;
            $res = $this->model->editFile($data1, ['id' => $raw['id']]);

        }else{
            $data['uid'] = $this->out['uid'];
            $data['atime']  = time();
            $data['mtime']  = '';
            $data['status'] = STATUS_ON;
            $data['check']  = LINE;
            $res = $this->model->addFile($data);
            $id = $res;


            $check['data'] = [
                'uid'    => $this->out['uid'],
                'modify' => ADD,
                'once'   => 2,
                'atime'  => time(),
                'desc'   => '',
            ];

            $table = TFILE;

            $check['data_ext'][] = [
                'tid'   => $res,
                'table' => $table,
                'data'  => ''

            ];
            $check = A('Client/Check')->addCheck($check, 1);
            if (!$res)
                goto END;

            $data1['check_id'] = $check;
            $res = $this->model->editFile($data1, ['id' => $res]);

        }

        if($raw['attach_ids']){
            $res = $this->model->editAttach(['pid'=>$id], ['id' => ['in',explode(',',$raw['attach_ids'])]]);
        }
        $ret = [ 'status' => E_OK, 'errstr' => '' ];


END:
        $this->retReturn($ret);
    }



    /**
     * 资源删除
     */
    public function del()
    {
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        if (!is_array($raw['id']) || !$raw['id'] ){
            $ret['status'] = E_DATA;

            $ret['errstr'] = 'wrong params';
             goto END;

        }


        $exist = $this->model->selectFile('id,attach_ids', ['id' => ['in', $raw['id']]]);


        if (count($exist) != count($raw['id'])) {
            $ret['status'] = E_NOEXIST;
            $ret['errstr'] = 'file not exists';
            goto END;
        }

        //删除文件
        $file_attach = $this->model->selectAttach('id', ['pid' => ['in', $raw['id']]]);

        $co = '';
        $attach_ids = [];
        foreach ($file_attach as $k => $v) {
            $attach_ids[] = $v['id'];
        }

        if ($attach_ids) {
            $del_file = $this->attachDel(['attach_ids' => $attach_ids]);
        }

        if ($del_file['status']){
            $ret['status'] = 9;
            $ret['errstr'] = 'attach del failed';
            goto END;

        }

        $where['id'] = ['in', $raw['id']];
        $res = $this->model->delFile($where);
        if (!$res){
            $ret['status'] = 8;
            $ret['errstr'] = 'path del failed';
            goto END;

        }

        $ret['status'] = E_OK;
END:

        $this->retReturn($ret);
    }



    /**
     * 资源附件删除
     */
    public function attachDel($data = '')
    {
        $raw = $data?$data:$this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        if (!is_array($raw['attach_ids']))
            goto END;

        $where['id'] = ['in', $raw['attach_ids']];
        $res = $this->model->selectAttach('', $where);

        foreach ($res as $v) {
            $this->fileDel($v['path']);
        }

        $res = $this->model->delAttach($where);

        if (!$res)
            goto END;

        $ret['status'] = E_OK;

END:
        $this->retReturn($ret);
    }



    /**
     * 文件删除
     */

    public function fileDel($file)
    {
        $res = false;
        if(file_exists($file))
            $res =  unlink($file);

        return $res;

    }

    /**
     * 评论
     */

    public function reply()
    {
        $ret = ['status' => E_SYSTEM, 'errstr' => ''];
        $raw = $this->RxData;
        $t = time();

        if ($this->out['status'] == U_NOTALK) {
            $ret['status'] = E_STATUS;
            goto END;
        }

        $keys = ['file_id', 'content'];
        foreach ($keys as $val) {
            if (!isset($raw[$val]) || $raw[$val] === '') {
                $ret['status'] = E_DATA;
                goto END;
            }

            $d[$val] = $raw[$val];
        }
        $d['reply_id'] = isset($raw['reply_id'])?$raw['reply_id']:0;

        $file_exist = $this->model->findFile('id,uid,title,ctime', ['id' => $raw['file_id'], 'check' => PASS]);
        if (!$file_exist) {
            $ret['status'] = E_NOEXIST;
            goto END;
        }

        $d['content'] = isset($raw['content']) ? $raw['content'] : '';

        if ($d['reply_id']>0) {
            $chk = $this->model->findReply('id,uid,top_id,content', ['id' => $d['reply_id']]);

            if (isset($chk['top_id']))
                $d['top_id'] = ($chk['top_id'] > 0) ? $chk['top_id'] : $d['reply_id'];

            if (!$chk) {
                $ret['status'] = E_NOEXIST;
                goto END;
            }
            $file_exist['title'] = $chk['content'];
            $file_exist['uid']   = $chk['uid'];
        }else {
            $d['top_id'] = $d['reply_id'];

        }

        $d['uid'] = $this->out['uid'];
        $d['atime'] = $t;
        $add_reply = $this->model->addReply($d);
        $inc_res = $this->model->incFile(['field'=>'reply_n','value'=>1],['id'=>$raw['file_id']]);

        if(!$add_reply || !$inc_res)
            goto END;

        $ret['id']     = $add_reply;

        //通知消息
        if($file_exist['uid'] == $this->out['uid'])
            goto STEP;
        $location = $this->model->selectReply('count(*) num', ['file_id' => $raw['file_id'],'place'=>0]);

        $msg_data = [
            'uid_from'  =>  $this->out['uid'],
            'uid_to'    =>  $file_exist['uid'],
            'reply_id'  =>  $add_reply,
            'pid'       =>  $raw['file_id'],
            'type'      =>  M_RESOURCE,
            'atime'     =>  time(),
            'title'     =>  $file_exist['title'],
            'location'  =>  $location[0]['num'],
        ];
        $res_msg = D('Client/Message')->addMessage($msg_data);
        $res_inc = D('Client/Message')->incMessage(['field'=>'resource_n','value'=>1],['uid'=>$file_exist['uid']]);
STEP:

        $ret['status'] = E_OK;
END:
        $this->retReturn($ret);
    }



    /**
     * 我的资源列表
     */

    public function myFiles()
    {
        $raw = $this->RxData;
        $ret = [];
        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page-1) . ',' . $num;

        $where['a.uid']   = $this->out['uid'];
        $where['a.type']  = $raw['type']?$raw['type']:1;
        $where['b.table'] = TFILE;

        if ($raw['name'])
            $where['a.title'] = ['like','%'.$raw['name'].'%'];

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



        $column = 'a.id,a.uid,a.title,a.img_path,a.browse_n,a.reply_n,a.atime,a.mtime,a.check,a.reason,
        b.data';

        $count  = $this->model->selectFileWithCheck('count(*) count', $where);

        $result = $this->model->selectFileWithCheck(
            $column,
            $where,
            $limit,
            'a.atime desc,a.mtime desc'
        );


        foreach($result as $k=>&$v){
            $v['mtime']   = $v['mtime']?$v['mtime']:'';
            if($v['data']){
                foreach(unserialize($v['data']) as $k1=>$v1){
                    $v[$k1] = $v1;
                }
            }
            unset($v['data']);
            $v['img_url'] = \Common\GetCompleteUrl($v['img_path']);
            unset($v['img_path']);

        }

        unset($v);
        $ret['page_start'] = $page;
        $ret['total'] = $count[0]['count'];
        $ret['data'] = $result?$result:[];
END:
        $this->retReturn($ret);
    }

    /**
     * 我上传的资源详情
     */
    public function myFileDetail()
    {

        $raw = $this->RxData;
        $ret = [];

        if (!is_numeric($raw['id']))
            goto END;

        $where['id']  = $raw['id'];
        $column = 'type,id,title,content,desc,author,atime,img_path,attach_ids';
        $res = $this->model->findFile('', $where);
        if (!$res)
            goto END;

        if($res['check'] == LINE){
            $res_check = $this->modelCheck->findCheckExtOnly('', ['tid'=>$raw['id'],'table'=>TFILE],1,'id desc');

            $data_check = unserialize($res_check['data']);
            foreach($data_check as $k=> $v){
                $res[$k] = $v;
            }
        }


        $res['file_n'] = count(explode(',',$res['attach_ids']));
        $res['img_url'] = \Common\GetCompleteUrl($res['img_path']);
        $res['img_thumb_url'] = \Common\GetCompleteUrl($res['img_path_thumb']);

        $ret = $res;

END:
        $this->retReturn($ret);
    }


    /**
     * 密钥
     */

    public function keyLists()
    {

        $raw = $this->RxData;
        $ret = [];
        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page-1) . ',' . $num;

        $where['a.uid'] = $this->out['uid'];

        if ($raw['keywords'])
            $where['c.title'] = ['like','%'.$raw['c.title'].'%'];

        if ($raw['check'])
            $where['a.check'] = $raw['check'];

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


        $count  = $this->model->selectKeysWithVideo('count(*) count', $where);
        $result = $this->model->selectKeysWithVideo(
            'a.*,b.nickname as uname,c.title as video_name',
            $where,$limit,
            'atime desc'
        );

        $ret['page_start'] = $page;
        $ret['total'] = $count[0]['count'];
        $ret['data'] = $result?$result:[];
END:
        $this->retReturn($ret);
    }

    public function applyKey()
    {

        $raw = $this->RxData;
        $ret = [];

        if (!$raw['video_id']){
            $ret['status'] = E_DATA;
            goto END;
        }

        $keys = ['device_id','rem','video_id'];
        $data = [];
        foreach($keys as $v){
            if($raw[$v])
                $data[$v] = $raw[$v];
        }

        $data['uid']   = $this->out['uid'];
        $data['atime'] = time();
        $res  = $this->model->addKey($data);

        if(!$res){
            $ret['status'] = E_SYSTEM;
            goto END;
        }
        $ret['status'] = E_OK;
        $ret['errstr'] = '';
END:
        $this->retReturn($ret);
    }



    /**
     * 资源附件列表
     */
    public function attachLists()
    {
        $raw = $this->RxData;
        $ret = [];

        $raw['attach_ids'] = str_replace("，", ",", $raw['attach_ids']);
        $where['id'] = ['in', explode(',', $raw['attach_ids'])];


        $ret = $this->model->selectAttach('id,pid,name,path,atime', $where);

        $file_info = $this->model->findFile('', ['id'=>$ret[0]['pid']]);
        if($file_info['type'] == FILE_DOC){
            foreach ($ret as $k=>&$v) {
                $v['url'] = \Common\GetCompleteUrl($v['path']);
                unset($ret[$k]['path']);
                unset($v['pid']);

            }
        }else{
            foreach ($ret as $k=>&$v) {
                unset($v['pid']);
                unset($v['path']);
            }
        }
END:
        $this->retReturn($ret);
    }

    /**
     * 资源列表
     */

    public function lists()
    {
        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page - 1) . ',' . $num;
        $ret['page_start'] = $page;

        $where['status'] = STATUS_ON;
        $where['check']  = PASS;
        if ($raw['type']) {
            $where['type'] = $raw['type'];
        } else {
            goto END;
        }
        if (isset($raw['name']))
            $where['title'] = ['like', '%' .$raw['name'] . '%'];

        if ($raw['time_start'])
            $where['atime'] = ['egt', $raw['time_start']];
        if ($raw['time_end'])
            $where['atime'] = ['lt', $raw['time_end']];

        $where['role_show'] = [];
        $where['role_show'][] = ['like','%,-1,%'];

        if($this->out['uid']){
            //用户的角色
            $user_role_info = D('Admin/AuthRule')->selectAuthGroupAccess('',['uid'=>$this->out['uid']]);
            foreach($user_role_info as $v){
                if($v['group_id'])
                    $where['role_show'][] = ['like','%,'.$v['group_id'].',%'];
            }
        }
        $where['role_show'][] = 'or';

        $count = $this->model->selectFile('count(*) total', $where);
        $result = $this->model->selectFile('id,img_path_thumb,title,browse_n,atime', $where, $limit, 'top asc,atime desc');

        if ($result) {
            foreach ($result as $k => &$v) {
                $v['img_url'] = \Common\GetCompleteUrl($v['img_path_thumb'] );
                unset($result[$k]['img_path']);
            }
            unset($v);
            $ret['data'] = $result ? $result : [];
            $ret['page_n'] = count($ret['data']);
        }

        if ($count)
            $ret['total'] = $count[0]['total'];

END:
        $this->retReturn($ret);
    }

    /**
     * 资源详情
     */
    public function detail()
    {
        $raw = $this->RxData;
        $ret = [];

        if (!is_numeric($raw['id']))
            goto END;

        $where['a.id'] = $raw['id'];
        $where['a.status'] = STATUS_ON;

        $res = $this->model->findFileWithUid('a.*,b.icon,b.nickname uname', $where);

        if($res['uid'] != $this->out['uid'] ){
            if($res['check'] != PASS)
                goto END;
        }

        if (!$res)
            goto END;

        $role_show = [];

        //可查看该文件的 的角色
        if($res['role_show'] == ',0,'){
            goto END;
        }elseif($res['role_show'] == ',-1,'){
            goto SHOW;
        }
        else{
            if($this->out['uid']){
                $roles_dn = explode(',',$res['role_show']);
                //用户的角色
                $user_role_info = D('Admin/AuthRule')->selectAuthGroupAccess('',['uid'=>$this->out['uid']]);
                $group_id = [];
                foreach($user_role_info as $v){
                    $group_id[] = $v['group_id'];
                }
                $cd = array_intersect ($roles_dn,$group_id);
                if(!$cd)
                    goto END;
            }else{
                goto END;
            }
        }

SHOW:

        $res_attach = $this->model->selectAttach('', ['pid'=>$raw['id']]);
        $res['file_n'] = count($res_attach);


        $res['img_url'] = \Common\GetCompleteUrl($res['img_path']);
        $res['img_thumb_url'] = \Common\GetCompleteUrl($res['img_path_thumb']);
        $res['read'] = $res['download'] = 2;

        $id = $raw['id'];
        $ip = get_client_ip();

        if(!S($ip.$id.'file')){
            S($ip.$id.'file',1);
            $this->model->incFile(['field'=>'browse_n','value'=>1],['id'=>$raw['id']]);

        }

        //文档
        if($res['type'] == FILE_DOC){
            if($res['role_read'] == ',-1,'){
                $res['read'] = 1;

            }else{
                if($this->out['uid']){
                    //可阅读该文件的 的角色
                    $roles = explode(',',$res['role_read']);
                    //用户的角色
                    $user_role_info = D('Admin/AuthRule')->selectAuthGroupAccess('',['uid'=>$this->out['uid']]);
                    $group_id = [];
                    foreach($user_role_info as $v){
                        $group_id[] = $v['group_id'];
                    }
                    $cd = array_intersect ($roles,$group_id);
                    if($cd)
                        $res['read'] = 1;
                }
            }

        }

        //可下载该文件的 的角色

        if($res['role_download'] == ',-1,'){
            $res['download'] = 1;

        }else{
            if($this->out['uid']){
                //可下载该文件的 的角色
                $roles_dn = explode(',',$res['role_download']);
                //用户的角色
                $user_role_info = D('Admin/AuthRule')->selectAuthGroupAccess('',['uid'=>$this->out['uid']]);
                $group_id = [];
                foreach($user_role_info as $v){
                    $group_id[] = $v['group_id'];
                }
                $cd = array_intersect ($roles_dn,$group_id);
                if($cd)
                    $res['download'] = 1;
            }
        }

        $res['icon'] = \Common\GetCompleteUrl($res['icon']);
        $ret = $res;

END:
        $this->retReturn($ret);
    }



    /**
     * 资源评论列表
     */
    public function replyList()
    {
        $raw = $this->RxData;
        $head = C('PREURL');
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];


        if (empty($raw['file_id']) || !is_numeric($raw['file_id']))
            goto END;

        $id = $raw['file_id'];

        $page_start = !isset($raw['page_start']) ? '1' : $raw['page_start'];
        $page_limit = !isset($raw['page_limit']) ? 10 : $raw['page_limit'];
        $limit = ($page_start - 1) * $page_limit . ',' . $page_limit;
        $ret['page_start'] = $page_start;

        $w = [
            'file_id' => $id,
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
        $res = $this->model->selectReply('', $wh, '', 'id asc');


        $uid = $reply_data = [];
        foreach ($res as $val) {
            $review_id[] = $val['id'];
            if (!in_array($val['uid'], $uid))
                $uid[] = $val['uid'];
        }


        if (isset($res) && !empty($res)) {

            $user_res = D('Profile')->selectClient('uid,icon,name', ['uid' => ['in', $uid]]);
            $user = [];
            if ($user_res)
                foreach ($user_res as $val) {
                    $user[$val['uid']] = [
                        'uid' => $val['uid'],
                        'name' => $val['name'],
                        'icon' => \Common\GetCompleteUrl($val['icon'])
                    ];

                }


            foreach ($res as $val) {
                $reply_data[$val['id']] = $val;

                $val['name'] = $user[$val['uid']]['name'];
                $val['icon'] = $user[$val['uid']]['icon'];

                $reply_info = $reply_data[$val['reply_id']];
                $user_this = $user[$reply_info['uid']];
                if ($val['top_id'] == 0) {
                    if (!$info[$val['id']]) {
                        $info[$val['id']] = $val;
                    }
                } else {
                    if($reply_data[$val['reply_id']]['top_id'] != 0){
                        $val['to_user'] = [
                            'uid'  => $user_this['uid'],
                            'name' => $user_this['name'],
                        ];

                    }
                    $info[$val['top_id']]['reply'][] = $val;
                }
            }

            foreach($info as &$v){

                if(!isset($v['reply']))
                    $v['reply'] = [];
            }
            if ($total)
                $ret['total'] = $total[0]['num'];

            $ret['page_n'] = count($info);
            $ret['data'] = array_reverse($info);
END:
            $this->retReturn($ret);
        }

    }


}



