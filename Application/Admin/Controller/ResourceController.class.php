<?php
namespace Admin\Controller;

class ResourceController extends GlobalController
{
    protected $model;
    protected $modelProfile;

    public function _initialize($check = true)
    {
        parent::_initialize($check);
        $this->modelProfile = D('Client/Profile');
        $this->model = D('Resource');
    }

    /**
     * 资源列表
     */
    public function Lists()
    {
        $raw = $this->RxData;
        $ret = ['page_start'=>1,'total'=>0,'page_n'=>0,'data'=>[]];
        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page-1) . ',' . $num;
        if ($raw['type']){
            $where['a.type'] = $raw['type'];
        }else{
            goto END;
        }
        if ($raw['check']){
            if(!in_array($raw['check'],[1,2,3,4])) goto END;
            $where['a.check'] = $raw['check'];
        }

        if ($raw['author'])
            $where['a.author'] = ['like','%'.$raw['author'].'%'];
        if ($raw['desc'])
            $where['a.desc'] = ['like','%'.$raw['desc'].'%'];
        if (isset($raw['name']))
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
        $where['b.table'] = TFILE;

        $head = C('PREURL');

        $result = $this->model->selectFileWithCheck('a.*,b.data,c.o_uid', $where,$limit,'a.top asc,a.atime desc');

        if(!$result)
            goto END;
        $count  = $this->model->selectFileWithCheck('count(*) count', $where);


//用户信息
        $uids = [];
        foreach($result as $v){
            if(!in_array($v['o_uid'],$uids))
                $uids[] = $v['o_uid'];
        }
        if($uids){
            $user_info = $this->modelProfile->selectClient('uid,nickname,name',['uid'=>['in',$uids]]);
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

        foreach($result as $k=>&$v){
            $v['mtime']   = $v['mtime']?$v['mtime']:'';

            if($v['data']){
                foreach(unserialize($v['data']) as $k1=>$v1){
                    $v[$k1] = $v1;
                }
            }
            unset($v['data']);
            $v['img_url'] = $v['img_path']?$head.$v['img_path']:'';
            $v['ouname'] = $v['o_uid']?$user[$v['o_uid']]['nickname']:'';

            unset($v['img_path']);
        }

        unset($v);
        $ret['page_start'] = $page;
        $ret['total'] = $count[0]['count'];
        $ret['page_n'] = count($result);
        $ret['data'] = $result?$result:[];
END:
        $this->retReturn($ret);
    }

    /**
     * 上传
     */
    public function upload(){
        $ret = [];
        $uid = $this->out['uid'];
        $head = C('PREURL');
        $raw = $this->RxData;


        $field = 'picture';
        $pre_path = C("UPLOAD_PATH");
        $file1 = '';
        $types = [];
        if($raw['type'] == 1){

            $types = ['doc', 'ppt', 'pptx','xml', 'txt', 'pdf'];
            $file1 = 'books';

        }elseif($raw['type'] == 2){
            $types = ['doc', 'ppt', 'pptx','xml', 'txt', 'pdf'];
            $file1 = 'docs';

        }elseif($raw['type'] == 3){
            $types = ['mp4', 'avi', 'wav'];
            $file1 = 'videos';
        }elseif($raw['type'] == 4){
            $types = ['jpg', 'jpeg', 'png','bim'];
            $file1 = 'pics';
        }

        if(!in_array(explode('.',$raw['name'])[1],$types)){
            $ret['status'] = E_FILE_TYPE;
            $ret['errstr'] = 'wrong file type';
            goto END;
        }

        $realpath = $pre_path.'resource'."/".$file1.'/uid_'.$uid.'/'.date('Y-m-d',time()).'/';
        $conf = array(
            'pre' => '',
            'types' => [
                'jpg', 'gif', 'png', 'jpeg',
                'mp4', 'avi', 'wav',
                'doc', 'ppt', 'pptx',
                'xml', 'txt', 'pdf', 'txt'],
        );

        if( !is_dir($realpath) ) $z = mkdir( $realpath, 0775, true );
        $upload_res = \Common\_Upload($field,$realpath,$conf);

        if( $upload_res['status'] != 0){
            $ret['status'] =  E_SYSTEM;
            $ret['errstr'] =  $upload_res;
            goto END;
        }

        foreach ($upload_res['file'] as $key => $value) {

            $file_path   = $value['savepath'].$value['savename'];
            $path        = $realpath.$value['savename'];
            $name = $value['savename'];
        }

        $thumb_res = \Common\_Thumb($file_path,$name,160,90);

        if( $upload_res['status'] != 0){
            $ret['status'] =  E_SYSTEM;
            $ret['errstr'] =  $upload_res;
            goto END;
        }


        $data = [
            'type'  =>  $raw['type'],
            'path'  =>  $path,
            'name'  =>  $value['savename'],
            'atime' =>  time(),
            'type'  =>  $raw['type']

        ];


        $ret[ 'file_url' ]  = $head.$path;
        if($raw['type'] < 4 ){
            $res = $this->model->addAttach($data);
            if(!$res){
                $ret['status'] =  E_SYSTEM;
                goto END;
            }
            $ret[ 'id' ]        = $res;

        }else{
            $ret[ 'thumb_path' ]= $thumb_res['savename'];
//            $ret[ 'file_url' ]  = $head.$thumb_res['path'];
        }

        $ret[ 'status' ]    = E_OK;
        $ret[ 'errstr' ]    = '';
        $ret[ 'path' ]      = $path;

END:
        $this->retReturn( $ret );
    }

    /**
     * 编辑
     */

    public function edit()
    {
        $raw = $this->RxData;
        $ret = [];
        $ret['status'] = E_OK;
        $ret['errstr'] = '';

        $keys = [
            'title', 'type', 'desc', 'author', 'img_path',
            'attach_ids','content','is_top','status'
        ];

        $data = [];
        foreach ($keys as $v) {
            if( isset( $raw[ $v ] ) && !empty( $raw[ $v ] ) )
                $data[$v] = $raw[$v];
        }
        if (empty($data)) {
            $ret['status'] = E_SYSTEM;
            goto END;
        }

        if(is_numeric($raw['id'])){
            $res = $this->model->editFile($data,['id'=>$raw['id']]);
        }else{
            $data['uid'] = $this->out['uid'];
            $data['atime'] = time();
            $data['status'] = 2;
            $data['uname'] = $this->out['name'];
            $data['icon']  = $this->out['icon'];
            $res = $this->model->addFile($data);
            if(!res){
                $ret['status'] = E_SYSTEM;
                goto END;
            }

        }
        if($raw['attach_ids'])
            $res = $this->model->editAttach(['pid'=>$raw['id']],['id'=>['in',$raw['attach_ids']]]);

END:
        $this->retReturn($ret);
    }

    /**
     * 编辑资源角色权限
     */

    public function editRoles()
    {
        $raw = $this->RxData;
        $ret = [];
        $ret['status'] = E_OK;
        $ret['errstr'] = '';

        $keys = [
            'role_download', 'role_show', 'role_read'
        ];

        if(!$raw['id']){
            $ret['status'] = E_SYSTEM;
            $ret['errstr'] = '';
            goto END;
        }



        $info = $this->model->findFile('id',['id'=>$raw['id']]);
        if(!$info){
            $ret['status'] = E_NOEXIST;
            $ret['errstr'] = '';
            goto END;
        }

        $roles = $data = [];

        $i = $j = 0;
        foreach ($keys as $v) {
            if( isset( $raw[ $v ] ) && !empty( $raw[ $v ] ) ){
                $i++;
                if($raw[$v][0] == '-1'){
                    $j = 1;
                    $data[$v] = ',-1,';
                }else{
                    $roles = array_merge($raw[$v],$roles);
                    $data[$v] = ',';
                    foreach ($raw[ $v ] as $item) {
                        $data[$v] .=  $item.',';
                    }
                }
            }else{
                $data[$v] = ',0,';

            }

        }

        if(!$i)
            goto END;

        if(!$j){
            $roles = array_unique($roles);

            $role_check = D('Admin/AuthRule')->selectAuthGroup('count(*) num',['id'=>['in',$roles]]);
            if($role_check[0]['num'] != count($roles)){
                $ret['status'] = E_NOEXIST;
                $ret['errstr'] = 'role not exists';
                goto END;
            }
        }

        $res = $this->model->editFile($data,['id'=>$raw['id']]);

END:
        $this->retReturn($ret);
    }


    /**
     * 资源详情
     */
    public function Detail()
    {
        $raw = $this->RxData;
        $res = [];

        if (!is_numeric($raw['id'])){
            goto END;
        }
        if($raw['type'])
            $where['a.type']  = $raw['type'];
        $where['a.id'] = $raw['id'];

        $ret  = $this->model->findFileWithUid('a.*,b.nickname uname',$where);

        //附件
        $ret_att = $this->model->selectAttach('',['pid'=>$raw['id']]);

        if($ret['check_id'] && $ret['check'] != PASS){

            $chk_info = D('Admin/Check')->selectCheckWithExt('a.modify,b.data',['a.id'=>$ret['check_id']]);
            if($chk_info[0]['modify'] == MODIFY)
                foreach(unserialize($chk_info[0]['data']) as $k=>$v){
                    $ret[$k.'_mod'] = $v;
                }
        }

        //全部角色id
        $role_check = D('Admin/AuthRule')->selectAuthGroup('id',['status'=>['neq',0]]);
        $all_roles = [];
        foreach($role_check as $v){
            $all_roles[] = $v['id'];
        }

        $keys = [
            'role_download', 'role_show', 'role_read'
        ];
        foreach($keys as $v){
            if($ret[$v]){
                if($ret[$v] == ',-1,'){
                    $ret[$v] = $all_roles;
                }else{
                    $temp = explode(',',$ret[$v]);
                    if(!(end($temp)))
                        array_pop($temp);
                    if(!(reset($temp)))
                        array_shift($temp);
                    $ret[$v] = $temp;
                }

            }
        }
        $head = C('PREURL');
        $ret['reason'] = $ret['rem'];
        $ret['img_url'] = $ret['img_path']?$head.$ret['img_path']:'';
        $ret['img_url_mod'] = $ret['img_path_mod']?$head.$ret['img_path_mod']:'';
        $res = $ret;

END:
        $this->retReturn($res);
    }


    /**
     * 资源删除
     */
    public function Del()
    {
        $raw = $this->RxData;

        $ret['status'] = E_SYSTEM;
        $ret['errstr'] = '';
        if (!is_array($raw['id']))
            goto END;

        $where['type']  = $raw['type'];
        $where['id']    = ['in',$raw['id']];
        $res  = $this->model->delFile($where);
        if(!$res)
            goto END;
        $ret['status'] = E_OK;


END:
        $this->retReturn($ret);
    }

    /**
     * 资源附件列表
     */
    public function AttachLists()
    {
        $raw = $this->RxData;
        $ret = [];

        $raw['attach_ids'] = str_replace("，",",",$raw['attach_id']);
        $ids = explode(',',$raw['attach_ids']);
        $where['id'] = ['in',$ids];

        $ret  = $this->model->selectAttach('id,name,path,atime',$where);

        $head = C('PREURL');
        foreach($ret as &$v){
            $v['url'] = $v['path']?$head.$v['path']:'';
        }

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

        if (!is_array($raw['attach_id']))
            goto END;

        $where['id'] = ['in', $raw['attach_id']];
        $res = $this->model->selectAttach('', $where);

        foreach ($res as $v) {
            if (!$this->fileDel($v['path'])) {
                $ret['status'] = E_NOEXIST;
                goto END;
            }
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

    public function Top(){
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $raw = $this->RxData;

        if( !in_array($raw['type'], [1,2,3]) )  goto END;
        if( !in_array($raw['top'], [1,2]) )     goto END;
        if( !is_array($raw['id'])  )  goto END;
        $res_file = $this->model->editFile(['top'=>$raw['top']],['id'=>['in',$raw['id']]]);

        $ret = [ 'status' => E_OK, 'errstr' => '' ];

END:
        $this->retReturn( $ret );
    }

    public function keyLists()
    {
        $raw = $this->RxData;
        $ret = [];
        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page-1) . ',' . $num;

        if ($raw['check'])
            $where['a.check'] = $raw['check'];

        if ($raw['keywords'])
            $where['c.title'] = ['like','%'.$raw['keywords'].'%'];

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

        $head = C('PREURL');
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


    public function addKey()
    {
        $raw = $this->RxData;
        $ret = [];

        if (!$raw['id']){
            $ret['status'] = E_DATA;
            $ret['errstr'] = 'wrong params';
            goto END;
        }

        $keys = ['key','check','reason'];

        if($raw['check'] == REJECT && empty($raw['reason'])){
            $ret['status'] = E_NOTENOUGH;
            $ret['errstr'] = 'rem not be null';
            goto END;

        }elseif($raw['check'] == PASS && empty($raw['key'])){
            $ret['status'] = E_NOTENOUGH;
            $ret['errstr'] = 'key not be null';
            goto END;
        }

        $data = [];
        foreach($keys as $v){
            if($raw[$v])
                $data[$v] = $raw[$v];
        }

        if($raw['check'] == REJECT )
            $data['key'] = '';


        $data['mtime'] = time();
        $res  = $this->model->saveKey($data,['id'=>$raw['id']]);

        if(!$res){
            $ret['status'] = E_SYSTEM;
            $ret['status'] = 'failed';

            goto END;
        }
        $ret['status'] = E_OK;
        $ret['errstr'] = '';
END:
        $this->retReturn($ret);
    }

}
