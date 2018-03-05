<?php
namespace Admin\Controller;

class ClassroomController extends GlobalController
{
    protected $model;
    protected $m_m;
    protected $m_m1;

    public function _initialize($check = false)
    {

        parent::_initialize($check );
        $this->model = D('Classroom');
    }

    //cate
    public function addCate()
    {
        $raw = $this->RxData;
        $ret = [];

        $keys = ['name', 'status'];

        $data = [];
        foreach ($keys as $v) {
            $data[$v] = $raw[$v];
        }
        if (empty($data)) {
            $ret['status'] = E_SYSTEM;
            goto END;
        }

        $data['atime'] = time();
        if($raw['id']){
            $res = $this->model->edit($data,['id'=>$raw['id']]);

        }else{
            $res = $this->model->addCate($data);
        }

        if (!$res) {
            $ret['status'] = E_SYSTEM;
            goto END;
        }


        END:
        $this->retReturn($ret);
    }


    public function editCate()
    {
        $raw = $this->RxData;
        $ret = [];

        $keys = ['name', 'status'];

        $data = [];
        foreach ($keys as $v) {
            $data[$v] = $raw[$v];
        }
        $data['atime'] = time();

        if (empty($data)) {
            $ret['status'] = E_SYSTEM;
            goto END;
        }
        $res = $this->model->edit($data,['id'=>$raw['id']]);

        if (!$res) {
            $ret['status'] = E_SYSTEM;
            goto END;
        }


        END:
        $this->retReturn($ret);
    }

    public function addList()
    {
        $raw = $this->RxData;
        $ret = [];

        $data['atime'] = time();
        if($raw['id']){
            $res = $this->model->edit($data,['id'=>$raw['id']]);

        }else{
            $res = $this->model->addCate($data);
        }
        $res = $this->model->edit($data,['id'=>$raw['id']]);


        if (!$res) {
            $ret['status'] = E_SYSTEM;
            goto END;
        }


        END:
        $this->retReturn($ret);
    }


    //course
    public function addCourse()
    {
        $raw = $this->RxData;
        $ret = [];
        $ret['status'] = E_OK;
        $ret['errstr'] = '';

        $keys = [
            'title', 'tags', 'btime', 'etime', 'level_id', 'desc',
            'content','teacher_uid','teacher_name','img_path','status'
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
        $data['uid'] = $this->out['uid'];
        $data['btime'] = $data['btime']?$data['btime']:time();
        unset($data['img_url']);
        if(is_numeric($raw['id'])){
            $data['mtime'] = time();
            $res = $this->model->editCourse($data,['id'=>$raw['id']]);
        }else{
            $data['atime'] = time();
            $res = $this->model->addCourse($data);
        }

        if (!$res) {
            $ret['status'] = E_SYSTEM;
            goto END;
        }


END:
        $this->retReturn($ret);
    }



    public function courseLists()
    {
        $raw = $this->RxData;
        $ret = [];

        $page = $raw['page_start'] ? $raw['page_start'] - 1 : 0;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * $page.','.$num;

        $where = [];
        $where['status'] = 1;

        if($raw['keywords'])
            $where['a.title'] = ['like','%'.$raw['keywords'].'%'];
        if($raw['teacher_name'])
            $where['a.teacher_name'] = ['like','%'.$raw['teacher_name'].'%'];
        if($raw['level_id'])
            $where['a.level_id'] = $raw['level_id'];

        if($raw['time_start'] && $raw['time_end']){
            $where['a.atime'] = [
                ['egt',$raw['time_start']],
                ['lt',$raw['time_end']]
            ];

        }elseif($raw['time_start'] ) {
            $where['a.atime'] = ['egt', $raw['time_start']];
        }elseif($raw['time_end'] ) {
            $where['a.atime'] = ['lt', $raw['time_end']];
        }


        $order = ['a.atime desc'];
        $result = $this->model->selectCourse('', $where, $limit, $order);

        if(!$result)
            goto END;
        $count = $this->model->selectCourse('',$where);

        //等级
        $level = \Common\getLevelInfo();
        foreach($result as &$v){
            $v['level'] = $level[$v['level_id']]['extend']?$level[$v['level_id']]['extend']:$level[$v['level_id']]['name'];
        }
        $ret['total'] = count($count);
        $ret['page_start'] = $page;
        $ret['page_n'] = count($result);
        $ret['data']   = $result;

END:
        $this->retReturn($ret);

    }

    public function upload(){
        $re = [];
        $uid = $this->out['uid'];

        $head = C('PREURL');

        $field = 'picture';
        $pre_path = C("UPLOAD_PATH");
        $realpath = $pre_path.'class/pic_'.time().'/';

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



    public function showCourse()
    {
        $raw = $this->RxData;
        $ret = [];

        if( !isset( $raw['id'] ) || empty( $raw['id'] ) )
            goto END;

        $where = [ 'a.id' => $raw['id'] ];

        $head = C('preurl');
        $column = [
            'a.id,a.btime,a.title,a.content,a.desc,a.status,a.teacher_uid,a.teacher_name,a.img_path,a.tags,
            a.level_id,b.name as level_name

        '];
        $order = ['a.top desc,a.mtime'];
        $result = $this->model->findCourse($column, $where);

        if( !$result ) goto END;

        $result['img_url'] = $result['img_path']?C('PREURL').$result['img_path']:'';
        $ret = $result;
END:
        $this->retReturn($ret);

    }


    public function delCate()
    {
        $raw = $this->RxData;
        $ret = ['status'=>E_OK,'errstr'=>''];

        $where = [];


        if(!is_numeric($raw['id']))
        {
            $ret['status'] = E_SYSTEM;
            goto END;
        }

        $where['id'] = $raw['id'];
        $exist = $this->model->selectCate( $where );

        if(!$exist){
            $ret['status'] = E_SYSTEM;
            goto END;
        }
        $exist_course = $this->model->selectCourse( '',['a.cate_id'=>['in',$raw['id']]] );


        if(!$exist_course){
            $ret['status'] = E_EXIST;
            goto END;
        }

        $ret = $this->model->delCate( $where );


END:
        $this->retReturn($ret);

    }

    public function delCourse()
    {
        $raw = $this->RxData;
        $ret = ['status'=>E_OK,'errstr'=>''];

        if(!count($raw['id']))
        {
            $ret['status'] = E_SYSTEM;
            $ret['errstr'] = 'illegal id';
            goto END;
        }
        foreach($raw['id'] as &$v){
            if(!is_numeric($v))
            {
                $ret['status'] = E_SYSTEM;
                $ret['errstr'] = 'illegal id';
                goto END;
            }
            $v = (int)$v;
        }


        $course_n = $this->model->selectCourse( '',['id'=>['in',$raw['id']]]);

        if(!$course_n){
            $ret['status'] = E_SYSTEM;
            $ret['errstr'] = 'not exist';
            goto END;
        }
        $del_course_n = $this->model->editCourse( ['status'=>2],['id'=>['in',$raw['id']]] );
        if($del_course_n !=count($raw['id'])){
            $ret['status'] = E_OK;
            $ret['errstr'] = 'part changed';
        }

END:
        $this->retReturn($ret);

    }

    public function teacherList()
    {
        $raw = $this->RxData;
        $ret = [];

        $page = $raw['page_start'] ? $raw['page_start'] : 1;

        $num  = $raw['page_limit'] ? $raw['page_limit'] : C('PAGE_LIMIT');
        $limit = $num * ($page-1).','.$num;

        $where = [];
        $quota = $raw['quota_left']?$raw['quota_left']:0;
        $level = $raw['level_id']?$raw['level_id']:0;

        $where['a.status'] = SYS_OK;
        $where['a.quota_left'] = ['gt',$quota];
        $where['b.level_id']   = ['gt',$level];
        $where['b.status_te']  = SYS_OK;


        if($raw['uids']){
            $level_max = 1;
            $user = \Common\getUserInfo($raw['uids']);
            foreach($user as $v){
                if($v['level_id'] > $level_max)
                    $level_max = $v['level_id'];
            }
            $where['b.level_id']   = ['gt',$level_max];

        }

        $order = ['a.quota_left desc'];
        $count = $this->model->selectTeacher('count(*) total',$where);
        $result = $this->model->selectTeacher('a.*,b.name,b.nickname,b.level_id', $where, $limit, $order);

        foreach($result as $k=>$v){
            if(in_array($v['uid'],$raw['uids']))
                unset($result[$k]);

        }
        $ret['page_start'] = $page;
        $ret['total']  = $count[0]['total'];
        $ret['page_n'] = count($result);
        $ret['data']   = $result;

END:
        $this->retReturn($ret);

    }

}
