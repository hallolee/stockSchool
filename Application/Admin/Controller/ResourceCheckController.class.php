<?php
namespace Admin\Controller;

class ResourceCheckController extends GlobalController
{
    protected $model;

    public function _initialize($check = true)
    {

        parent::_initialize($check);
        $this->model = D('Resource');
    }


    /**
     * 资源列表
     */
    public function Lists()
    {
        $raw = $this->RxData;
        $ret = [];
        $page = $raw['page_start'] ? $raw['page_start'] - 1 : 0;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * $page . ',' . $num;


//        $where['status'] = 2;
        if ($raw['type']){
            $where['type'] = $raw['type'];
        }else{
            goto END;
        }
        if ($raw['status'])
            $where['status'] = $raw['status'];

        if ($raw['author'])
            $where['author'] = ['like','%'.$raw['author'].'%'];
        if ($raw['desc'])
            $where['desc'] = ['like','%'.$raw['desc'].'%'];
        if ($raw['title'])
            $where['title'] = ['like','%'.$raw['title'].'%'];


        if ($raw['time_start'])
            $where['atime'] = ['egt',$raw['time_start']];
        if ($raw['time_end'])
             $where['atime'] = ['lt',$raw['time_end']];


        $head = C('PREURL');

        $count  = $this->model->selectFile('id', $where);
        $result = $this->model->selectFile('', $where,$limit,'top desc,atime desc');

        foreach($result as $k=>&$v){
            $v['img_url'] = $v['img_path']?$head.$v['img_path']:'';
            unset($v['img_path']);
        }

        unset($v);
        $ret['page_start'] = $page + 1;
        $ret['total'] = count($count);
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
        if($raw['type'] == 1)
            $file1 = 'books';
        if($raw['type'] == 2)
            $file1 = 'docs';
        if($raw['type'] == 3)
            $file1 = 'videos';

        $realpath = $pre_path.'resource'."/".$file1.'/uid_'.$uid.'/'.date('Y-m-d',time()).'/';

        $conf = array(
            'pre' => 'icon',
            'types' => [
                'jpg', 'gif', 'png', 'jpeg',
                'mp4', 'avi', 'wav',
                'doc', 'ppt', 'pptx',
                'xml', 'txt', 'pdf', 'txt'],
        );

        if( !is_dir($realpath) ) $z = mkdir( $realpath, 0775, true );
        $upload_res = \Common\_Upload($field,$realpath,$conf);

        if( $upload_res['status'] != 0 ){
            $ret['status'] =  E_SYSTEM;
            $ret['errstr'] =  $upload_res;
            goto END;
        }

        foreach ($upload_res['file'] as $key => $value) {

            $file_path = $value['savepath'].$value['savename'];
            $path = $realpath.$value['savename'];
        }

        $data = [
            'type'  =>  $raw['type'],
            'path'  =>  $path,
            'atime' =>  time(),
            'type'  =>  $raw['type']

        ];

        if($raw['type'] < 4 ){
            $res = $this->model->addAttach($data);
            if(!$res){
                $ret['status'] =  E_SYSTEM;
                goto END;
            }
            $ret[ 'id' ]        = $res;

        }

        $ret[ 'status' ]    = E_OK;
        $ret[ 'errstr' ]    = '';
        $ret[ 'file_url' ]  = $head.$path;
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
            'attach_id','content','is_top','status'
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
            $res = $this->model->addFile($data);
            if(!res){
                $ret['status'] = E_SYSTEM;
                goto END;
            }

        }
END:
        $this->retReturn($ret);
    }


    /**
     * 资源详情
     */
    public function Detail()
    {
        $raw = $this->RxData;
        $ret = [];

        if (!is_numeric($raw['id'])){
            goto END;
        }
        $where['type']  = $raw['type'];
        $where['id'] = $raw['id'];

        $ret  = $this->model->findFile('',$where);

        $head = C('PREURL');
        $ret['img_url'] = $ret['img_path']?$head.$ret['img_path']:'';

END:
        $this->retReturn($ret);
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

        if (!is_array(($raw['attach_id']))){
            goto END;
        }

        $raw['attach_id'] = str_replace("，",",",$raw['attach_id']);
        $ids = explode(',',$raw['attach_id']);
        $where['id'] = $ids;

        $ret  = $this->model->selectAttach('id,name,path',$where);

        $head = C('PREURL');
        foreach($ret as $v){
            $v['url'] = $v['path']?$head.$v['path']:'';
        }

END:
        $this->retReturn($ret);
    }

    /**
     * 资源附件删除
     */
    public function AttachDel()
    {
        $raw = $this->RxData;
        $ret = [];

        $ret['status'] = E_SYSTEM;
        $ret['errstr'] = '';
        if (!is_array(($raw['attach_id']))){
            goto END;
        }

        $where['id'] = ['in',$raw['attach_id']];
        $res  = $this->model->selectAttach('path',$where);

        foreach($ret as $v){
            if(!$this->fileDel($v['path'])){
                $ret['status'] = E_NOEXIST;
                goto END;

            }
        }

        $ret  = $this->model->delAttach($where);

        if(!ret)
            goto END;

        $ret['status'] = E_OK;

END:
        $this->retReturn($ret);
    }



}