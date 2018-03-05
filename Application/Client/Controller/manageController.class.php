<?php
namespace Admin\Controller;

class ResourceController extends GlobalController
{
    protected $model;

    public function _initialize($check = false)
    {
        parent::_initialize($check);
        $this->model = D('Resource');
    }


    /**
     * 资源列表
     */
    public function myFiles()
    {
        $raw = $this->RxData;
        $ret = [];
        $page = $raw['page_start'] ? $raw['page_start'] : 0;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page-1) . ',' . $num;

        $where['uid'] = $this->out['uid'];
        if ($raw['type']){
            $where['type'] = $raw['type'];
        }else{
            goto END;
        }
        if ($raw['status']){
            if(!in_array($raw['status'],[1,2,3,4])) goto END;
            $where['status'] = $raw['status'];
        }

        if ($raw['name'])
            $where['title'] = ['like','%'.$raw['name'].'%'];

        if($raw['time_start'] && $raw['time_end']){
            $where['atime'] = [
                ['egt',$raw['time_start']],
                ['lt',$raw['time_end']]
            ];
        }elseif($raw['time_start'] ) {
            $where['atime'] = ['egt', $raw['time_start']];
        }elseif($raw['time_end'] ){
            $where['atime'] = ['lt',$raw['time_end']];
        }



        $head = C('PREURL');

        $count  = $this->model->selectFile('count(*) count', $where);
        $result = $this->model->selectFile('', $where,$limit,'top desc,atime desc');

        foreach($result as $k=>&$v){
            $v['img_url'] = $v['img_path']?$head.$v['img_path']:'';
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
    public function AttachDel()
    {
        $raw = $this->RxData;
        $ret = [];

        $ret['status'] = E_SYSTEM;
        $ret['errstr'] = '';
        if (!is_array(($raw['attach_ids']))){
            goto END;
        }

        $where['id'] = ['in',$raw['attach_ids']];
        $res  = $this->model->selectAttach('path,pid',$where);
        if(!$res){
            $ret['status'] = E_NOEXIST;
            goto END;
        }

        foreach($res as $v){
            if(!$this->fileDel($v['path'])){
                $ret['status'] = E_NOEXIST;
                goto END;

            }
        }

        $ret_del  = $this->model->delAttach($where);

        $res_att  = $this->model->selectAttach('id',['pid'=>$res[0]['pid']]);


        $attach_ids = '';
        foreach ($res_att as $k=>$item) {

            if($k>0){
                $attach_ids .= ','.$item['id'];
            }else{
                $attach_ids .=$item['id'];
            }
        }


        $res_file = $this->model->editFile(['attach_ids'=>$attach_ids],['id'=>$res[0]['pid']]);

//        var_dump(M()->getLastSql());
//        var_dump($attach_ids);die;

        if(!ret)
            goto END;

        $ret['status'] = E_OK;

END:
        $this->retReturn($ret);
    }




    public function Top(){
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $raw = $this->RxData;

        if( !in_array($raw['type'], [1,2,3]) )  goto END;
        if( !in_array($raw['top'], [1,2]) )     goto END;
        if( !is_array($raw['id'])  )  goto END;
        $raw['top'] = $raw['top']>1?0:1;
        $res_file = $this->model->editFile(['top'=>$raw['top']],['id'=>['in',$raw['id']]]);

        $ret = [ 'status' => E_OK, 'errstr' => '' ];

END:
        $this->retReturn( $ret );
    }



}