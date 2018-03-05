<?php
namespace Client\Controller;

class HomeController extends GlobalController{
    protected $home;
    protected $adminHome;

    public function _initialize($check = false){
        parent::_initialize($check);
        $this->home      = D('Client/Home');
        $this->adminHome = D('Admin/Home');
    }


    //公告列表-首页
    public function systemInfo(){
        $raw = $this->RxData;
        $ret = [ 'total' => 0 , 'page_start' => 0, 'page_n' => 0, 'data' => [] ];

        $page = $raw['page_start'] ? $raw['page_start']: 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page-1) . ',' . $num;
        $ret['page_start'] = $page;

        $where['status'] = STATUS_ON;
        $where['btime'] = ['lt',time()];
        if ($raw['type'])
            $where['type'] = $raw['type'];
        if ($raw['title'])
            $where['title'] = $raw['title'];

        $count = $this->home->findSystemInfo('count(*) num', $where);
        $result = $this->home->selectSystemInfo('id,title,btime', $where, $limit, 'btime desc,id');


        $item_name = $this->home->findSystemInfoType('name', ['id'=>$raw['type']]);
        $ret['item_name'] = $item_name?$item_name['name']:'';

        if( $result )
        {
            $ret['page_n'] = count($result);
            $records = [];
            foreach ($result as $k=>$v){
                $dateStr = date('Y-m-d',$v['btime']);
                $v['atime'] = $v['btime'];
                $timestamp0 = strtotime($dateStr);
                if(!isset( $records[$timestamp0]))
                    $records[$timestamp0]['time'] = $timestamp0;
                $records[$timestamp0]['list'][] = $v;
            }

            $t = [];
            foreach($records as $v){
                $t[] = $v;
            }

            $ret['data'] = $this->sortt($t,false);
        }

        if( $count )
            $ret['total'] = $count['num'];
END:
        $this->retReturn($ret);
    }

    /**
     * @param $a
     * @param bool|false $type  desc
     * @return mixed
     */
    public function sortt($a,$type=false){
        //从小到大
        $len = count($a);
        for($i=1;$i<=$len;$i++)
        {
            for($j=$len-1;$j>=$i;$j--)
            {
                if($type){
                    if($a[$j]['time']<$a[$j-1]['time'])
                    {
                        //如果是从大到小的话，只要在这里的判断改成if($b[$j]>$b[$j-1])就可以了
                        $tmp=$a[$j];
                        $a[$j]=$a[$j-1];
                        $a[$j-1]=$tmp;
                    }
                }else{
                    if($a[$j]['time']>$a[$j-1]['time'])
                    {
                        //如果是从大到小的话，只要在这里的判断改成if($b[$j]>$b[$j-1])就可以了
                        $tmp=$a[$j];
                        $a[$j]=$a[$j-1];
                        $a[$j-1]=$tmp;
                    }
                }

            }
        }
        return $a;
    }


    //公告列表
    public function noticeList(){
        $raw = $this->RxData;
        $head = C('PREURL');
        $ret = [ 'total' => 0 , 'page_start' => 0, 'page_n' => 0, 'data' => [] ];

        $page = $raw['page_start'] ? $raw['page_start']: 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page-1) . ',' . $num;
        $ret['page_start'] = $page;

        $where['status'] = STATUS_ON;
        $where['btime'] = ['lt',time()];


        if ($raw['type'])
            $where['type'] = $raw['type'];
        if ($raw['title'])
            $where['title'] = $raw['title'];

        $count = $this->home->findSystemInfo('count(*) num', $where);
        $result = $this->home->selectSystemInfo('id,type,title,desc,tags,btime as atime', $where, $limit, 'btime desc');

        $guide = $this->home->findSystemTypeInfo('name', ['id'=>$raw['type']]);
        $ret['guide'] = $guide['name']? $guide['name']:'迷路了';

        if( $count )
            $ret['total'] = $count['num'];

        if( $result ){
            $ret['page_n'] = count($result);
            $ret['data'] = $result;

        }
END:
        $this->retReturn($ret);
    }


    //公告详情
    public function noticeDetail(){
        $raw = $this->RxData;
        $ret = [];

        if (!isset( $raw['id'] ) || !is_numeric( $raw['id'] ))
            goto END;

        $id = $raw['id'];
        $ip = get_client_ip();

        S(['expire'=>C('EXPIRE_CACHE')]);
        if(!S($ip.$id)){
            S($ip.$id,1);
            $this->home->incSystemInfo( ['id'=>$raw['id']], ['column' => 'read_n', 'value' => 1] );
        }

        //关于我们
        if($id == -1){
            $result = $this->home->findSystemInfoWithItem('a.*,b.name as guide', ['a.type'=>SYS_INFO_ABOUT]);

        }else{
            $result = $this->home->findSystemInfoWithItem('a.*,b.name as guide', ['a.id'=>$raw['id'],'a.status'=>STATUS_ON]);
            if(!$result)
                goto END;

            $result_pre = $this->home->findSystemInfo('',
                [
                    'type'=>$result['type'],
                    'btime'=>['lt',$result['btime']],
                    'status'=>STATUS_ON,
                    'id'=>['neq',$raw['id']]

                ],
                'btime desc,mtime desc'
            );

            $result_next0 = $this->home->selectSystemInfo(
                'id,title,desc,tags,btime as atime,status',
                [
                    'type'=>$result['type'],
                    'btime'=>['gt',$result['btime']],
                    'status'=>STATUS_ON,'id'=>['neq',$raw['id']]
                ], '',
                'atime,mtime'
            );


            $result_next = [];
            foreach ($result_next0 as $item) {
                if($item['atime'] > time())
                    continue;

                $result_next = $item;break;
            }

            $result['prev'] = $result_pre['id']?$result_pre['id']:0;
            $result['next'] = $result_next['id']?$result_next['id']:0;

        }
        $result['atime'] = $result['btime'];
        $result['author'] = $result['author']?$result['author']:AUTHOR;
        $result['source'] = $result['source']?$result['source']:SOURCE;
        $ret = $result ? $result : [];

END:
        $this->retReturn($ret);
    }

    public function show404(){
        $raw = $this->RxData;
        $ret = [];

        $result = $this->home->findSystemInfo('title,content,source_url', ['type'=>SYS_SITE_CLOSE]);

        $head = C('PREURL');
        if($result){
            $result['url'] = $result['source_url']?$head.$result['source_url']:'';
            unset($result['source_url']);
        }


        $ret = $result ? $result : [];

END:
        $this->retReturn($ret);
    }

    //站点基本信息
    public function showConfig(){
        $ret = [];
        $head = C('PREURL');
        $result = $this->home->selectBasic('field,value', ['module' => BASIC_SITE]);
        foreach ($result as $v) {
            $ret[$v['field']] = $v['value'];
        }
        $ret['site_logo']    =  $ret['site_logo']?$head . $ret['site_logo']:'';
        $ret['site_qr_code'] =  $ret['site_logo']?$head . $ret['site_qr_code']:'';
END:
        $this->retReturn($ret);
    }

    //站点seo信息
    public function seoInfo(){
        $ret = [];
        $result = D('Client/Home')->selectBasic('field,value', ['module' => BASIC_SEO]);
        foreach($result as $v){
            $ret[$v['field']] = $v['value'];
        }
END:
        $this->retReturn($ret);
    }

    //广告列表
    public function adList(){
        $raw = $this->RxData;
        $ret = [];
        $head = C('PREURL');

        if(!$raw['type'])
            goto END;

        $result = $this->home->selectAd(
            'id,path,rem,status,sort,jump_url,atime',
            ['type'=>$raw['type'],'status'=>['neq',STATUS_OFF]],
            '',
            'sort'
        );

        foreach($result as &$v){
            $v['url'] = $v['path']?$head.$v['path']:$v['path'];
            unset($v['path']);
        }
        $ret = $result;
END:
        $this->retReturn($ret);
    }



    //导航
    public function topNav(){
        $ret = [];

        $raw = $this->RxData;
        $where['pid']    = DEF_PID;
        $where['status'] = NAV_ON;
        if($raw['place'] && in_array($raw['place'],[1,2]))
            $where['place'] = $raw['place'];

        $ret_top = $this->home->selectNav('id,name,url,type,item_id,sort',$where,'','sort,mtime desc');


        $items =  $this->adminHome->selectSystemInfoType('id,status');
        $item_kv= [];

        foreach($items as $v){
            $item_kv[$v['id']] = $v['status'];
        }

        $second_item   = $this->home->selectNavItem(
            'a.id,a.pid,a.url,a.name,a.type,a.sort as time',
            ['a.item_id'=>['gt',0],'a.status'=>NAV_ON,'a.pid'=>['gt',0]],
            '',
            'a.sort desc,a.mtime'
        );

        $second_normal = $this->home->selectNavTop(
            'id,pid,url,name,type,sort as time',
            ['item_id'=>0,'status'=>NAV_ON,'pid'=>['gt',0]],
            '',
            'sort desc,mtime'
        );

        $rets = array_merge($second_normal,$second_item);

        $rets = $this->sortt($rets,true);

        $data = [];
        foreach($rets as $v) {
            $data[$v['pid']][] = [
                'url'  => $v['url'],
                'name' => $v['name'],
                'type' => $v['type']
            ];

        }
        foreach($ret_top as $k=>$v){
            if($ret_top[$k]['type'] == JUMP_SECOND)
                $ret_top[$k]['second_nav'] = $data[$v['id']];
        }

        foreach($ret_top as $v){
            $ret[] = $v;
        }
END:
        $this->retReturn($ret);


    }


    //友情链接列表
    public function fLinkInfo(){
        $raw = $this->RxData;
        $ret = [];
        $head = C('PREURL');

        $result = $this->home->selectAd(
            'path,jump_url',
            ['type'=>4,'status'=>STATUS_ON],
            '',
            'sort'
        );

        foreach($result as &$v){
            $v['url'] = \Common\GetCompleteUrl($v['path']);
            $v['path'] = \Common\GetCompleteUrl($v['path']);
        }
        $ret = $result;

END:
        $this->retReturn($ret);
    }


    /**
     * 管理专区菜单列表
     */
    public function showMenu(){
        $ret = ['common'=>[],'teacher'=>[]];

        $where['status'] = SYS_OK;
        if(!$this->out['uid']){
            $where['role'] = ['like','%,'.UNLOGIN.',%'];
        }else{
            foreach(unserialize($this->out['role_id']) as $v){
                $where['role'][] = ['like','%,'.$v.',%'];
            }
            $where['role'][] = 'or';
        }

        $res = $this->home->selectMenu('',$where,'','');

        if( !$res )
            goto END;
        foreach ($res as $re) {
            $ret[$re['class']][] = ['name'=>$re['name'],'url'=>$re['url']];
        }
END:
        $this->retReturn( $ret );
    }

}
