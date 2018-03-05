<?php
namespace Client\Controller;

class ClassroomController extends GlobalController
{
    protected $model;
    protected $m_m;
    protected $m_m1;
    protected $modelProfile;

    public function _initialize($check = false)
    {
        parent::_initialize($check);
        $this->model = D('Classroom');
        $this->modelProfile = D('Client/Profile');

    }

    //cate
    public function addCate()
    {
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        $keys = ['name', 'status'];
        $data = [];
        foreach ($keys as $v) {
            if( isset( $raw[$v] ) && $raw[$v] !== '' )
                $data[$v] = $raw[$v];
        }

        if (empty($data)) 
            goto END;

        $data['atime'] = time();
        if($raw['id']){
            $res = $this->model->edit($data,['id'=>$raw['id']]);

        }else{
            $res = $this->model->addCate($data);
        }
        if (!$res) 
            goto END;

        $ret['status'] = E_OK;
END:
        $this->retReturn($ret);
    }


    public function editCate()
    {
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        $keys = ['name', 'status'];
        $data = [];
        foreach ($keys as $v) {
            if( isset( $raw[$v] ) && $raw[$v] !== '' )
                $data[$v] = $raw[$v];
        }
        $data['atime'] = time();

        if (empty($data)) 
            goto END;

        $res = $this->model->edit($data,['id'=>$raw['id']]);

        if (!$res) 
            goto END;

        $ret['status'] = E_OK;
END:
        $this->retReturn($ret);
    }

    public function addList()
    {
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        $data['atime'] = time();
        if($raw['id']){
            $res = $this->model->edit($data,['id'=>$raw['id']]);

        }else{
            $res = $this->model->addCat($data);
        }
        $res = $this->model->edit($data,['id'=>$raw['id']]);

        if (!$res)  goto END;

        $ret['status'] = E_OK;
END:
        $this->retReturn($ret);
    }


    //course
    public function addCourse()
    {
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        $keys = ['title','tags','level', 'desc','content','teacher_uid'];
        $data = [];
        foreach ($keys as $v) {
            if( isset( $raw[$v] ) && $raw[$v] !== '' )
                $data[$v] = $raw[$v];
        }

        $data['img_path'] = $raw['img_url']?$raw['img_url']:'';
        $data['level_id'] = $raw['level']?$raw['level']:'';
        if (empty($data)) 
            goto END;

        $data['path'] = $data['img_url'];
        $data['uid'] = $this->out['uid'];
        unset($data['img_url']);
        if(is_numeric($raw['id'])){
            $data['mtime'] = time();
            $res = $this->model->editCourse($data,['id'=>$raw['id']]);
        }else{
            $data['atime'] = time();
            $res = $this->model->addCourse($data);
        }

        if (!$res)
            goto END;

        $ret['status'] = E_OK;
END:
        $this->retReturn($ret);
    }



    public function courseLists()
    {
        $raw = $this->RxData;
        $head = C('preurl');
        $ret = [ 'total' => 0 , 'page_start' => 0, 'page_n' => 0, 'data' => [] ];

        $page = $raw['page_start'] ? $raw['page_start']: 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page-1) . ',' . $num;
        $ret['page_start'] = $page;

        $where = [];
        $where['status'] = SYS_OK;

        if($raw['teacher_name'])
            $where['teacher_name'] = ['like','%'.$raw['teacher_name'].'%'];
        if($raw['keywords'])
            $where['title'] = ['like','%'.$raw['keywords'].'%'];
        if($raw['level_id'])
            $where['level_id'] = $raw['level_id'];


        $order = ['top desc,atime desc'];
        $result = $this->model->selectCourseOnly('', $where, $limit, $order);
        if(!$result)
            goto END;
        $count = $this->model->selectCourseOnly('count(*) num',$where);


        if( $count )
            $ret['total'] = $count['num'];

        $level = \Common\getLevelInfo();
        if( $result ){
            foreach($result as &$v){
                $v['img_url'] = \Common\GetCompleteUrl($v['img_path_thumb']);
                unset($v['img_path']);
                $v['level_name'] = $v['level_name']?$v['level_name']:$v['level'];
                $v['level_name'] = $level[$v['level_id']]['extend'];
            }

            $ret['page_n'] = count($result);
            $ret['data'] = $result;
        }
END:
        $this->retReturn($ret);
    }



    public function showCourse()
    {
        $raw = $this->RxData;
        $ret = [];

        if(!is_numeric($raw['id']))
            goto END;

        $where['a.id'] = $raw['id'];
        if($raw['keywords'])
            $where['a.title'] = ['like','%'.$raw['keywords'].'%'];
        if($raw['cat'])
            $where['b.id'] = $raw['cat'];

        $where['a.status'] = SYS_OK;

        $column = [
            'a.id,a.title,a.level_id,a.tags,a.content,a.desc,a.status,a.img_path,
             c.extend as level_name,c.name as level,a.name
            ,a.btime
             '];
        $result = $this->model->findCourse('', $where);

        $level = \Common\getLevelInfo();
        $result['level_name'] =
            $level[$result['level_id']]['extend']
                ?$level[$result['level_id']]['extend']:
            $level[$result['level_id']]['name'];

        if(!$result)
            goto END;

        $result['img'] = \Common\GetCompleteUrl($result['img_path']);
        $result['img_thumb'] = \Common\GetCompleteUrl($result['img_path_thumb']);

        $ret = $result;
END:
        $this->retReturn($ret);

    }


    public function delCat()
    {
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        if(!is_numeric($raw['id']))
            goto END;

        $where['id'] = $raw['id'];
        $exist = $this->model->selectCat( $where );

        if(!$exist)
            goto END;

        $exist_course = $this->model->selectCourse( '',['a.cat_id'=>$raw['id']] );

        if(!$exist_course){
            $ret['status'] = E_EXIST;
            goto END;
        }

        $res = $this->model->delCat( $where );

        if( $res )
            $ret['status'] = E_OK;
END:
        $this->retReturn($ret);

    }

    public function levelList(){
        $ret = [];
        $where['status'] = ['neq',SYS_FORBID];
        $res = $this->modelProfile->selectLevel('',$where);
        if( $res )
            $ret = $res;

        $this->retReturn( $ret );
    }

}
