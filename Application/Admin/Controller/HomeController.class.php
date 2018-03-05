<?php
namespace Admin\Controller;

class HomeController extends GlobalController
{
    protected $home;
    protected $system;

    public function _initialize($check = true)
    {

        parent::_initialize($check);
        $this->home = D('Home');
        $this->system = D('System');
    }

    public function editAd()
    {
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM ];

        if($raw['id']) {
            $keys = ['sort','rem','status','jump_url'];

        }else{
            $keys_m = ['path'];
            $keys = ['sort','rem','status','jump_url'];
        }

        foreach ($keys as $v){
            if( isset( $raw[$v] ) )
                $data[$v] = $raw[$v];
        }
        $data['atime'] = time();

        if($raw['id']){
            if(isset($raw['path']) && !empty($raw['path']))
                $data['path'] = $raw['path'];

            $ad_exist = $this->home->findAd('id', ['id'=>$raw['id']]);
            if(!$ad_exist){
                $ret['status'] = E_EXIST;
                $ret['error']  = '不存在的id';
            }

            $result = $this->home->editAd($data, ['id'=>$raw['id']]);

        }else{
            //必填
            foreach ($keys_m as $v){
                if( empty( $raw[$v] ) ){
                    $ret['status'] = E_DATA;
                    $ret['error']  = 'lack params ';
                    goto END;
                }
                $data[$v] = $raw[$v];
            }
            $result = $this->home->addAd($data);
        }



        if(!$result){
            $ret['status'] = E_MYSQL;
            $ret['error']  = 'add failed';
        }
        $ret['status'] = E_OK;
        $ret['error']  = '';

END:
        $this->retReturn($ret);
    }

    //广告列表
    public function adList()
    {
        $raw = $this->RxData;
        $ret = [];
        $head = C('PREURL');
        if(!$raw['type'])
            goto END;

        $column = 'id,path,rem,status,sort,jump_url,atime';
        $result = $this->home->selectAd($column,['type'=>$raw['type']],'','sort');

        foreach($result as &$v){
            $v['url'] = $v['path']?$head.$v['path']:$v['path'];
            unset($v['path']);
        }
        $ret = $result;

END:
        $this->retReturn($ret);
    }

    //广告删除

    public function adDel()
    {
        $raw = $this->RxData;
        $ret = [];
        foreach($raw['id'] as $v){
            if(!is_numeric($v)){
                $ret['status'] = E_SYSTEM;
                $ret['errstr'] = 'wrong id';
                goto END;
            }
        }

        $exist = $this->home->selectAd('',['id'=>['in',$raw['id']]],'','sort');
        foreach($exist as $v){
            if($v['status'] == 2){
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'illegal exist';
                goto END;
            }
        }
        if(count($exist) < count($raw['id']) ){
            $ret['status'] = E_NOEXIST;
            $ret['errstr'] = 'not exist';
            goto END;
        }
        $result = $this->home->delAd(['id'=>['in',$raw['id']]]);

        $ret['status'] = E_OK;
        $ret['errstr'] = '';

END:
        $this->retReturn($ret);
    }


    public function editSystemNotice(){
        $raw = $this->RxData;
        $ret = [ 'status' => 0, 'errstr' => '' ];

        $d = [];
        $keys = [ 'title', 'content','author', 'source','source_url','tags', 'btime','type', 'desc', 'status' ];
        foreach ($keys as $val) {
            if( isset( $raw[ $val ] ) && !empty( $raw[ $val ] ) )
                $d[ $val ] = $raw[ $val ];
        }
        $id = isset( $raw['id'] )?$raw['id']:'';
        $d['mtime'] = time();
        $d['btime'] = $raw['btime']?$raw['btime']:time();

        if( $id && is_numeric( $id ) ){
            $exist = $this->home->findSystemInfo('', ['id'=>$raw['id']]);

            if(!$exist){
                $ret['status'] = E_EXIST;
                $ret['error']  = '不存在的id';
            }

            $res = $this->home->saveSystemInfo( $d,['id' => $id ] );

            if(!$res){
                $ret['status'] = E_MYSQL;
                goto END;
            }
        }else{
            $d['xiaozhu'] = $this->out['xiaozhu'];
            $d['name']    = $this->out['name'];
            $d['phone']   = $this->out['phone'];
            $res = $this->home->addSystemInfo( $d );
            if( $res === false ){
                $ret['status'] = E_MYSQL;
                goto END;
            }
        }

END:
        $this->retReturn( $ret );
    }


    public function setSystemNoticeStatus(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        $keys = [ 'id', 'status' ];
        foreach ($keys as $val) {
            if( !isset( $raw[ $val ] ) )
                goto END;
            ${$val} = $raw[  $val  ];
        }

        if( !is_array( $id ) || !in_array( $status, [1,2] ) ) goto END;
        $res = $this->home->saveSystemInfo( [ 'status' => $status, 'mtime' => time() ] , [ 'id' => [ 'in', $id ] ]);
        if( $res !== false ){
            $ret['status'] = E_OK;
        }

END:
        $this->retReturn( $ret );
    }

    //公告列表
    public function showSystemNotice()
    {
        $raw = $this->RxData;
        $ret = [];
        $page = $raw['page_start']?$raw['page_start']-1:0;
        $num = $raw['page_limit']? $raw['page_limit']:10;
        $limit = $num*$page.','.$num;

        $where['status'] = ['in',[1,2]];
        if($raw['type'])
            $where['type'] = $raw['type'];
        if($raw['title'])
            $where['title'] = ['like','%'.$raw['title'].'%'];

        $count  = $this->home->selectSystemInfo('',$where,'','btime desc,mtime desc');
        $result = $this->home->selectSystemInfo('id,title,name,status,btime as mtime',$where,$limit,'btime desc,id');

        $ret['page_start'] = $page+1;
        $ret['page_n'] = count($result);
        $ret['page_total'] = count($count);
        $ret['data'] = $result;

END:
        $this->retReturn($ret);
    }


    //公告详情
    public function showSystemNoticeDetail()
    {
        $raw = $this->RxData;
        $ret = [];

        $id = $raw['id'];
        if( !$id && !is_numeric( $id ) )
            goto END;

        if($raw['type'])
            $where['type'] = $raw['type'];

        if($id == -1){
            $result = $this->home->findSystemInfo('', ['type'=>SYS_INFO_ABOUT]);

        }else{
            $result = $this->home->findSystemInfo('', ['id'=>$raw['id']]);

        }

        $ret = $result?$result:[];
        $ret['atime'] = $result['mtime'];
        unset($ret['mtime']);

END:
        $this->retReturn($ret);
    }


    //公告删除
    public function noticeDel()
    {
        $raw = $this->RxData;
        $ret = [];
        $ret['status'] = 0;
        $ret['errstr'] = '';

        $id = $raw['id'];
        if( !$id && !is_numeric( $id ) )
            goto END;
        foreach($id as $v){
            if(!is_numeric($v)){
                $ret['status'] = 1;
                $ret['errstr'] = '';
                goto END;
            }
        }

        $result = $this->home->saveSystemInfo( [ 'status' => 3, 'mtime' => time() ] , [ 'id' => [ 'in', $id ] ]);


END:
        $this->retReturn($ret);
    }


//导航

//导航
    public function topNav(){
        $ret = [];

        $raw = $this->RxData;

        $column = 'a.*,c.name as item_name';

        $where['a.pid'] = DEF_PID;
        if($raw['place'] && in_array($raw['place'],[1,2]))
            $where['a.place'] = $raw['place'];

        if($raw['type'] && in_array($raw['type'],[1,2,3]))
            $where['a.type'] = $raw['type'];
        if($raw['id']){
            $where['a.id'] = ['neq',$raw['id']];
            $where['a.class'] = NAV_USER;
            $column = 'a.id,a.name';
        }
        $ret_top = $this->home->selectNavItem(
            $column,
            $where,
            '',
            'a.sort,mtime desc');

END:
        $this->retReturn($ret_top);


    }

    public function navDetail(){

        $ret = [];
        $raw = $this->RxData;

        $ret = $this->home->findNav('id,name,url,status,sort',['id'=>$raw['id']]);
        $ret['second_nav'] = [];
        $second = $this->home->selectNavWithItems('a.*,b.name as item_name',['a.pid'=>$raw['id']],'','sort,mtime desc');
        if($second){
            foreach($second as $v){
                $ret['second_nav'][] = $v;
            }

        }

END:
        $this->retReturn($ret);

    }

    public function setNavOrder(){

        $ret = [];
        $raw = $this->RxData;

        $keys = ['id','to_id'];
        foreach($keys as $v){
            if(!$raw[$v]){
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong params';
                goto END;
            }
        }

        $res = $this->home->findNav('sort,id',['id'=>$raw['id']]);
        $res_to  = $this->home->findNav('sort,id',['id'=>$raw['to_id']]);
        if(!$res || !$res_to){
            $ret['status'] = E_NOEXIST;
            goto END;
        }

        $o  = $this->home->saveNav(['sort'=>$res_to['sort']],['id'=>$raw['id']]);
        $to = $this->home->saveNav(['sort'=>$res['sort']],['id'=>$raw['to_id']]);

        if(!$o || !$to){
            $ret['status'] = E_SYSTEM;
            goto END;
        }
        $ret['status'] = E_OK;

END:
        $this->retReturn($ret);


    }

    public function addNav(){

        $ret = [ 'status' => E_OK, 'errstr' => '' ];
        $raw = $this->RxData;
        $colinfo = [];

        //part edit
        if($raw['edit'])
            goto EDIT;

        if($raw['type']){
            if(!in_array($raw['type'],[1,2,3])){
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong type';
                goto END;
            }
        }
        if(empty($raw['name'])){
            $ret['status'] = E_DATA;
            $ret['errstr'] = 'error data';
            goto END;
        }

        if($raw['status']){
            if(!in_array($raw['status'],[1,2])){
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong status';
                goto END;
            }
        }

EDIT:
        if($raw['place']) {
            if (!in_array($raw['place'], [1, 2])) {
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong place';
                goto END;
            }
        }

        if($raw['pid']){
            // check exists
            $where1['id']   = $raw['pid'];
            $where1['type'] = JUMP_SECOND;
            $item = $this->home->findNavTop('id',$where1);
            if(!$item){
                $ret['status'] = E_NOEXIST;
                goto END;
            }
        }

        if($raw['item_id']){
            $colinfo = $this->home->findSystemInfoType('id,name',['id'=>$raw['item_id']]);
            if(!$colinfo){
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'wrong item_id';
                goto END;
            }
        }

        $keys = ['id', 'name','url','sort','item_id','status','type','place'];
        foreach($keys as $v){
            if(isset($raw[$v]) && !empty($raw[$v]))
                $data[$v] = $raw[$v];
        }

        if(isset($raw['pid'])) $data['pid'] = $raw['pid'];

        // check edit
        if(is_numeric($data['id'])) {

            $where['id'] = $data['id'];
            //sysnav check
            $item_info = $this->home->findNavTop('',$where);
            if(!$item_info){
                $ret['status'] = E_NOEXIST;
                $ret['errstr'] = 'wrong id';
                goto END;
            }

            if($data['place']){

                // second changed to first
                if( $item_info['place'] == NAV_SECOND && $data['place'] == NAV_FIRST ){
                    // check children
                    $item_place = $this->home->findNavTop('id',['pid'=>$data['id']]);
                    if($item_place){
                        $ret['status'] = E_AC;
                        $ret['errstr'] = 'second navs that still exists';
                        goto END;
                    }

                    // check quota
                    $count = $this->home->selectNav('count(*) count', ['pid' => DEF_PID, 'place' => NAV_FIRST]);


                    if ($count[0]['count'] >= MAX_FIRST ) {
                        $ret['status'] = E_NAV_FIRST_LIMIT;
                        $ret['errstr'] = 'nav1 nums limited ';
                        goto END;
                    }

                    // first changed to second
                }elseif( $item_info['place'] == NAV_FIRST && $data['place'] == NAV_SECOND ){
                    // check quota
                    $count = $this->home->selectNav('count(*) count', ['pid' => DEF_PID, 'place' => NAV_SECOND]);
                    if ($count[0]['count'] >= MAX_SECOND ) {
                        $ret['status'] = E_NAV_SECOND_LIMIT;
                        $ret['errstr'] = 'nav2 nums limited ';
                        goto END;
                    }
                }
            }

            if($data['type']){
                if($data['type'] != JUMP_SECOND){
                    $item = $this->home->findNavTop('id',['pid'=>$data['id']]);
                    if($item){
                        $ret['status'] = E_SECOND;
                        $ret['errstr'] = 'second navs that still exists';
                        goto END;
                    }

                }
            }

            if(
                ( isset($data['place']) && $data['place'] != $item_info['place'] )
                ||
                ( isset($data['pid']) && $data['pid'] != $item_info['pid'] )
            )
                $data['sort'] = 99;


            if( $data['pid'] != $item_info['pid']){
                if($raw['edit']){
                    if($item_info['type'] == JUMP_SECOND) {
                        $ret['status'] = E_NAV_STATUS;
                        $ret['errstr'] = 'status not match';
                        goto END;

                    }
                    $item = $this->home->findNavTop('id',['pid'=>$data['id']]);
                    if($item ){
                        $ret['status'] = E_SECOND;
                        $ret['errstr'] = 'second navs that still exists';
                        goto END;
                    }
                }
                $count = $this->home->selectNav('count(*) as num',['pid'=>$data['pid']]);
                if($count['num'] >= MAX_S_NAV){
                    $ret['status'] = E_LIMIT;
                    goto END;
                }
            }


            if($item_info['class'] == NAV_SYS){
                $keys = ['url','item_id','pid','type'];
                foreach($keys as $v){
                    if($raw[$v]) unset($data[$v]);
                }
            }

            // check insert
        }else{

            if($data['pid']){
                $count = $this->home->selectNav('count(*) as num',['pid'=>$data['pid']]);
                if($count['num'] >= MAX_S_NAV){
                    $ret['status'] = E_LIMIT;
                    goto END;
                }
            }
            elseif( !$data['place'] ){
                $ret['status'] = E_DATA;
                $ret['errstr'] = 'v_nav place required.';

                goto END;
            }
            //top nav nums check
            elseif($data['place'] == NAV_FIRST ){
                $count = $this->home->selectNav('count(*) count',['pid'=>DEF_PID,'place'=>NAV_FIRST]);
                if($count[0]['count'] >= MAX_FIRST){
                    $ret['status'] = E_NAV_FIRST_LIMIT;
                    $ret['errstr'] = 'nav3 nums limited ';

                    goto END;
                }
            }elseif($data['place'] == NAV_SECOND ){
                $count = $this->home->selectNav('count(*) count',['pid'=>DEF_PID,'place'=>NAV_SECOND]);
                if($count[0]['count'] >= MAX_SECOND) {
                    $ret['status'] = E_NAV_SECOND_LIMIT;
                    $ret['errstr'] = 'nav4 nums limited ';
                    goto END;
                }
            }

        }


        if($data['item_id'] && $data['type'] == JUMP_OWN){
            $data['name']   =  $data['name']?$data['name']:$colinfo['name'];
            $data['url']    =  ITEM_URL.$data['item_id'];
        }

        $data['mtime']  =  time();

        if(is_numeric($data['id'])){
            $res = $this->home->saveNav($data,$where);
        }else{
            $res = $this->home->addNav($data);
            if(!$res ){
                $ret['status'] = 1;
                goto END;
            }

        }


END:
        $this->retReturn($ret);


    }


    public function delNav()
    {
        $raw = $this->RxData;
        $ret = [];
        $ret['status'] = E_OK;
        $ret['errstr'] = '';

        $id = $raw['id'];
        if( !$id && !is_numeric( $id ) ){
            $ret['status'] = E_DATA;
            goto END;
        }

        $nav_info = $this->home->findNav('id',['id'=>$raw['id']]);
        if(!$nav_info){
            $ret['status'] = E_NOEXIST;
            goto END;
        }

        if($nav_info['status'] == NAV_SYS){
            $ret['status'] = E_AC;
            goto END;
        }

        $nav_second = $this->home->findNav('id',['pid'=>$raw['id']]);

        if($nav_second){
            $ret['status'] = E_SECOND;
            $ret['errstr'] = 'second navs has still exist';
            goto END;
        }

        $result = $this->home->delNav( [ 'id' => $raw['id'] ]);
        if(!$result){
            $ret['status'] = E_SYSTEM;
            goto END;
        }


END:
        $this->retReturn($ret);
    }

    //栏目类型

    public function addItemType(){

        $ret = [ 'status' => E_OK, 'errstr' => '' ];

        $raw = $this->RxData;
        $keys = ['name','status'];
        foreach($keys as $v){
            $data[$v] = $raw[$v];
        }
        if($raw['id']){
            $result = $this->home->editSystemInfoType($data,['id'=>$raw['id']]);

        }else{
            $result = $this->home->addSystemInfoType($data);
            if(!$result) {
                $ret['status'] = E_SYSTEM;
                goto END;
            }
        }
END:
        $this->retReturn($ret);


    }



    public function show404()
    {
        $raw = $this->RxData;
        $ret = [];

        $result = $this->home->findSystemInfo('title,content,source_url', ['type'=>SYS_SITE_CLOSE]);

        $head = C('PREURL');
        if($result)
            $result['url'] = $result['source_url']?$head.$result['source_url']:'';

        unset($result['source_url']);

        $ret = $result ? $result : [];

END:
        $this->retReturn($ret);
    }
    public function edit404(){
        $raw = $this->RxData;
        $ret = [ 'status' => 0, 'errstr' => '' ];

        $d = [];
        $keys = [ 'title', 'content','path' ];
        foreach ($keys as $val) {
            if( isset( $raw[ $val ] ) && !empty( $raw[ $val ] ) )
                $d[ $val ] = $raw[ $val ];
        }

        if(empty($d)){
            $ret = [ 'status' => 1, 'errstr' => '' ];
            goto END;


        }
        $d['source_url'] = $d['path'];
        unset($d['path']);
        $res = $this->home->saveSystemInfo( $d,['type' => SYS_SITE_CLOSE ] );


END:
        $this->retReturn( $ret );
    }




    public function itemTypeList(){

        $ret = [ ];
        $raw = $this->RxData;
        $where['id'] = ['gt',1];
        if(is_numeric($raw['status'])){
            if($raw['status'] == 2){
                $where['status'] = $raw['status'];
            }else{
                $where['status'] =['neq',2];
            }

        }
        $ret = $this->home->selectSystemInfoType('id,name,status',$where);

END:
        $this->retReturn($ret);


    }

    public function upload(){
        $re = [];
        $uid = $this->out['uid'];

        $head = C('PREURL');

        $field = 'picture';
        $pre_path = C("UPLOAD_PATH");
        $realpath = $pre_path.'profile'."/".'uid_'.$uid.'/icon/';

        $conf = array(
            'pre' => 'pro',
            'types' => ['jpg', 'gif', 'png', 'jpeg'],
        );

        if( !is_dir($realpath) ) $z = mkdir( $realpath, 0775, true );
        $upload_res = \Common\_Upload($field,$realpath,$conf);

        if( $upload_res['state'] != 0 ){
            $re =  json_encode($upload_res);
            goto END;
        }


        foreach ($upload_res['file'] as $key => $value) {

            $file_path = $value['savepath'].$value['savename'];
            $path = $realpath.$value['savename'];
        }


        $re[ 'status' ] = 0;
        $re[ 'errstr' ] = '';
        $re[ 'url' ] = $head.$path;
        $re[ 'path' ] = $head.$path;
END:
        $this->retReturn( $re );
    }

    public function del_file( $filename='' ){

        if (!file_exists($filename))
            return false;

        return unlink($filename);
    }


    //站点seo信息
    public function seoInfo()
    {
        $ret = [];
        $result = D('Client/Home')->selectBasic('field,value', ['module' => BASIC_SEO]);
        foreach($result as $v){
            $ret[$v['field']] = $v['value'];
        }
END:
        $this->retReturn($ret);
    }

    //编辑seo信息
    public function editSeo()
    {
        $ret = ['status'=>E_OK,'errstr'=>''];
        $raw = $this->RxData;
        $keys = ['seo_content','seo_keywords'];
        if(!$raw['seo_content'] && !!$raw['seo_keywords']){
            $ret = [ 'status' => E_DATA, 'errstr' => '' ];
            goto END;

        }
        foreach ($keys as $key) {
            if($raw[$key])
                $result = $this->system->editBasic(['value'=>$raw[$key]],['module'=>BASIC_SEO,'field'=>$key]);
        }
END:
        $this->retReturn($ret);
    }


    //友情链接编辑
    public function editFLink()
    {
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM ];
        $keys = ['sort','rem','status','jump_url'];

        if($raw['status'] && !in_array($raw['status'],[1,2])){
            $ret['status'] = E_DATA;
            $ret['error']  = 'wrong status ';
            goto END;
        }

        foreach ($keys as $v){
            if( isset( $raw[$v] )  )
                $data[$v] = $raw[$v];
        }
        if($raw['path'])
            $data['path'] = $raw['path'];

        $data['atime'] = time();

        if(!$raw['id'])
            goto END;

        $ad_exist = $this->home->findAd('id', ['id'=>$raw['id'],'type'=>FLINK]);
        if(!$ad_exist){
            $ret['status'] = E_NOEXIST;
            $ret['error']  = '不存在的id';
            goto END;
        }

        $result = $this->home->editAd($data, ['id'=>$raw['id']]);

        if(!$result){
            $ret['status'] = E_MYSQL;
            $ret['error']  = 'add failed';
        }
        $ret['status'] = E_OK;
        $ret['error']  = '';

END:
        $this->retReturn($ret);
    }


    //友情链接列表
    public function fLinkInfo()
    {
        $raw = $this->RxData;
        $ret = [];
        $head = C('PREURL');

        $result = $this->home->selectAd('',['type'=>FLINK],'','sort');

        foreach($result as &$v){
            $v['url'] = \Common\GetCompleteUrl($v['path']);
            $v['path'] = \Common\GetCompleteUrl($v['path']);
        }
        $ret = $result;

END:
        $this->retReturn($ret);
    }


}
