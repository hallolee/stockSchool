<?php

namespace Admin\Controller;

class DownloadController extends GlobalController
{


    protected $m_m;
    protected $RxData;
    protected $model;
    protected $modelProfile;
    protected $modelUser;
    protected $modelMember;

    public function _initialize($check = true)
    {
        parent::_initialize($check = false);
        $this->modelMember   = D('Admin/Membership');
        $this->modelProfile         = D('Admin/Profile');
        $this->modelUser = D('Admin/User');
        $this->model     = D('Admin/Resource');
        $this->RxData    = I('get.');
        //get 获取token 验证登录
        if( !isset( $this->out['uid'] ) || empty( $this->out['uid'] ) || !is_numeric( $this->out['uid'] ) ){
            $ret = [ 'status' => E_TOKEN, 'errstr' => '' ];
            $raw = I('get.');
            if( !isset( $raw['token'] ) ){
                $this->retReturn( $ret );
            }else{
                \Common\ValidDaTokenFile( $raw['token'], $user );
                if( !isset( $user ) || empty( $user ) || empty( $user['uid'] ) ){
                    $this->retReturn( $ret );
                }else{
                    $this->out = $user;
                }
            }
        }


    }


    public function exportInviteUser()
    {
        $raw = I('get.');

        $ret = [];
        $where = [];

        $keys_up = ['up_nickname','up_name'];
        $keys = ['nickname','name','phone'];
        foreach ( $keys as $item) {
            if($raw[$item])
                $where['a.'.$item] = ['like','%'.$raw[$item].'%'];
        }

        foreach ( $keys_up as $item) {
            if($raw[$item]){
                $item0 = $item;
                $item1 = str_replace('up_','',$item0);
                $where['d.'.$item1] = ['like','%'.$raw[$item].'%'];;
            }
        }

        if($raw['up_uid'])
            $where['d.uid'] = $raw['up_uid'];
        if($raw['up_xiaozhu'])
            $where['d.xiaozhu'] = $raw['up_xiaozhu'];
        if($raw['uid'])
            $where['a.uid'] = $raw['uid'];
        if($raw['xiaozhu'])
            $where['a.xiaozhu'] = $raw['xiaozhu'];


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

        $column = [
            'a.uid,a.xiaozhu,a.nickname,a.status,a.name,a.phone,a.login_time,a.atime,a.invite_n,
            d.xiaozhu as up_xiaozhu,d.uid as up_uid,d.name as up_name,d.nickname as up_nickname,d.qq as up_qq'
        ];

        $order = ['a.atime desc'];

        $data = $this->modelUser->selectUserInvite($column,$where,'',$order);

        $data = $data['re'];


        $title_num = array('A', 'B', 'C', 'D', 'E','F','G','H','I','J');
        $title = [
            'A' => "小筑号",
            'B' => "用户姓名",
            'C' => "用户昵称",
            'D' => "QQ",
            'E' => "上线小筑号",
            'F' => "上线姓名",
            'G' => "上线昵称",
            'H' => "上线QQ",
            'I' => "下线人数",
            'J' => "注册时间",

        ];


        $name = urlencode('【量学小筑】用户关系列表');
        $ext = [
            'WIDTH'=>25,
            'A'=>15,
            'I'=>10,
            'J'=>30,
        ];
        $this->excel($data, $title_num, $title, $name, 'addExcelInviteUser',$ext);

    }


    public function exportMembership()
    {
        $raw = I('get.');
        if($raw['check'])
            $where['a.check']= $raw['check'];
        if($raw['level_id'])
            $where['a.level_id']= $raw['level_id'];

        $uid0 =$uid1 = [];

        if ($raw['name']){
            $user_info = $this->modelProfile->selectClient('uid',['name'=>['like','%'.$raw['name'].'%']]);

            if(!$user_info)
                goto END;
            foreach($user_info as $v){
                $uid0[] = $v['uid'];
            }
        }
        if ($raw['nickname']){
            $user_info = $this->modelProfile->selectClient('uid',['nickname'=>['like','%'.$raw['nickname'].'%']]);

            if(!$user_info)
                goto END;
            foreach($user_info as $v){
                $uid1[] = $v['uid'];
            }

            if($raw['name']){
                if(!array_intersect($uid0,$uid1))
                    goto END;
                $uid0 = array_merge($uid0,$uid1);
            }else{
                $uid0 = $uid1;
            }
            unset($uid1);
        }


        $uid3 = $uid4 =$t = [];
        if ($raw['teacher_name']){
            $user_info = $this->modelProfile->selectClient('uid',['name'=>['like','%'.$raw['teacher_name'].'%']]);

            if(!$user_info)
                goto END;
            foreach($user_info as $v){
                $uid3[] = $v['uid'];
            }
        }

        if ($raw['teacher_nickname']){

            $user_info = $this->modelProfile->selectClient('uid',['nickname'=>['like','%'.$raw['teacher_nickname'].'%']]);

            if(!$user_info)
                goto END;
            foreach($user_info as $v){
                $uid4[] = $v['uid'];
            }
            if($raw['teacher_name']){
                if(!array_intersect($uid3,$uid4)){
                    goto END;
                }
                $uid3 = array_merge($uid3,$uid4);
            }else{
                $uid3 = $uid4;
            }
        }

        if($raw['teacher_name'] || $raw['teacher_nickname'])
            $where['a.teacher_uid'] = ['in',$uid3];
        if($raw['name'] || $raw['nickname'])
            $where['a.uid'] = ['in',$uid0];


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

        //等级
        $level_info = $this->modelProfile->selectLevel('id,name');
        $level = [];
        foreach($level_info as $v)
            $level[$v['id']] = $v['name'];

        switch($raw['type']){
            case 3:
                $data = $this->showApplyStuList($raw,$level,$where);
                break;
            case 4:
                $data = $this->showChangeTeaList($raw,$level,$where);
                break;
            case 5:
                $data = $this->showApplyTeaList($raw,$level,$where);
                break;
            case 6:
                $data = $this->showUpgradeList($raw,$level,$where);
                break;
            default:
                $raw['type'] =3;
                $data = $this->showApplyStuList($raw,$level,$where);
                break;
        }
END:
        $title_num = array('A', 'B', 'C', 'D', 'E','F','G','H','I','J');
        $title = [
            'A' => "学员姓名",
            'B' => "学员昵称",
            'C' => "学员等级",
            'D' => "教员姓名",
            'E' => "教员昵称",
            'F' => "申请类型",
            'G' => "申请时间",
            'H' => "状态",
            'I' => "审核人",
            'J' => "审核时间",
        ];


        $name = urlencode('【量学小筑】成教申请数据');

        $ext = [
            'WIDTH'=>25,
            'B'=>13,
            'C'=>13,
            'G'=>30,
            'H'=>15,
            'I'=>15,
            'J'=>30
        ];

        $this->excel($data, $title_num, $title, $name, 'addExcelMembership',$ext);

    }


    /**
     * 申请学员记录列表
     */

    protected function showApplyStuList($raw,$level,$where){

        $column = 'a.id,a.uid,a.level_id,a.teacher_uid,a.reason,a.check,a.atime,a.mtime,b.o_uid';
        $res = $this->modelMember->selectRecord(
            $column,
            $where,
            '',
            'a.id desc'
        );

        if(!$res)
            goto END;

        //用户信息
        $uids = [];
        foreach($res as $k=>$v){
            $uids[] = $v['o_uid'];
            $uids[] = $v['uid'];
            $uids[] = $v['teacher_uid'];
        }

        $user_info = $this->modelProfile->selectClient('icon,level_id,uid,nickname,name',['uid'=>['in',$uids]]);
        $user = [];
        if($user_info)
            foreach ($user_info as $val) {
                $user[$val['uid']] = [
                    'uid'   =>  $val['uid'],
                    'name'  =>  $val['name'],
                    'nickname'  =>  $val['nickname'],
                    'icon'   =>  \Common\GetCompleteUrl($val['icon'])
                ];
            }


        foreach($res as $k=>&$v){
            $v['type'] = $raw['type'];
            $v['ouname']   = $user[$v['o_uid']]['nickname'];
            $v['name']     = $user[$v['uid']]['name'];
            $v['nickname'] = $user[$v['uid']]['nickname'];
            $v['teacher_name'] = $user[$v['teacher_uid']]['name'];
            $v['teacher_nickname'] = $user[$v['teacher_uid']]['nickname'];
            $v['level']        = $level[$v['level_id']];//申请时的level_id
        }


END:

        return $res;

    }



    /**
     * 申请成为教员列表
     */

    protected function showApplyTeaList($raw,$level,$where){

        $res = $this->modelMember->selectRecordTea(
            'a.*,b.o_uid',
            $where,
            '',
            'a.id desc'
        );

        if(!$res)
            goto END;

        //用户信息
        $uids = [];
        foreach($res as $k=>$v){
            $uids[] = $v['o_uid'];
            $uids[] = $v['uid'];
            $uids[] = $v['teacher_uid'];
        }

        $user_info = $this->modelProfile->selectClient('icon,level_id,uid,nickname,name',['uid'=>['in',$uids]]);
        $user = [];
        if($user_info)
            foreach ($user_info as $val) {
                $user[$val['uid']] = [
                    'uid'   =>  $val['uid'],
                    'name'  =>  $val['name'],
                    'nickname'  =>  $val['nickname'],
                    'icon'   =>  \Common\GetCompleteUrl($val['icon'])
                ];
            }


        foreach ($res as &$v) {
            $v['type']      = $raw['type'];
            $v['ouname']   = $user[$v['o_uid']]['nickname'];
            $v['name']   = $user[$v['uid']]['name'];
            $v['nickname'] = $user[$v['uid']]['nickname'];
            $v['teacher_name'] = $user[$v['teacher_uid']]['nickname'];
            $v['level']        = $level[$v['level_id']];//申请时的level_id
        }



END:

        $this->retReturn($res);

    }

    /**
     * 申请变更教员列表
     */

    protected function showChangeTeaList($raw,$level,$where){

        $res = $this->modelMember->selectRecordChgTea(
            'a.*,b.o_uid',
            $where,
            '',
            'a.id desc'
        );


        if(!$res)
            goto END;

        $user = $uids = [];
        foreach($res as $v){
            $uids[]     = $v['o_uid'];
            $uids[]     = $v['uid'];
            $uids[]     = $v['teacher_uid'];
            $uids[]     = $v['new_teacher_uid'];
        }

        if($uids)
            $user = \Common\getUserInfo($uids);

        foreach ($res as &$re) {
            $re['type']  = $raw['type'];
            $re['level'] = $level[$re['level_id']];
            $re['ouname']    = $user[$re['o_uid']]['nickname'];
            $re['name']     = $user[$re['uid']]['name'];
            $re['nickname'] = $user[$re['uid']]['nickname'];
            $re['teacher_name'] = $user[$re['new_teacher_uid']]['nickname'];
//            $re['new_teacher_name'] = $user[$re['new_teacher_uid']]['nickname'];
        }
END:

        return $res;

    }


    /**
     * 为学员升星记录
     */

    protected function showUpgradeList($raw,$level,$where){

        $column = 'a.*,b.o_uid';
        $res = $this->modelMember->selectRecordUpgrade(
            $column,
            $where,
            '',
            'a.id desc'
        );

        if(!$res)
            goto END;

        //用户信息
        $uids = [];
        foreach($res as $k=>$v){
            $uids[] = $v['o_uid'];
            $uids[] = $v['uid'];
            $uids[] = $v['teacher_uid'];
        }

        $user_info = $this->modelProfile->selectClient('icon,level_id,uid,nickname,name',['uid'=>['in',$uids]]);
        $user = [];
        if($user_info)
            foreach ($user_info as $val) {
                $user[$val['uid']] = [
                    'uid'   =>  $val['uid'],
                    'name'  =>  $val['name'],
                    'nickname'  =>  $val['nickname'],
                    'icon'   =>  \Common\GetCompleteUrl($val['icon'])
                ];
            }


        foreach ($res as &$v) {
            $v['type']      = $raw['type'];
            $v['ouname']    = $user[$v['o_uid']]['nickname'];
            $v['name']  = $user[$v['uid']]['name'];
            $v['nickname']  = $user[$v['uid']]['nickname'];
            $v['teacher_name'] = $user[$v['teacher_uid']]['nickname'];
            $v['level']        = $level[$v['level_id']];//申请时的level_id
        }

END:

        return $res;

    }




    protected function addExcelInviteUser(&$objExcel, $data = [],$ext = []){

        $i = 3;
        foreach ($data as $value) {
            /*----------写入内容-------------*/

            $objExcel->getActiveSheet()->setCellValue('A' . $i, $value['xiaozhu']?$value['xiaozhu']:'/');
            $objExcel->getActiveSheet()->setCellValue('B' . $i, $value['name']);
            $objExcel->getActiveSheet()->setCellValue('C' . $i, $value['nickname']?$value['nickname']:'/');
            $objExcel->getActiveSheet()->setCellValue('D' . $i, $value['qq']?$value['qq']:'/');
            $objExcel->getActiveSheet()->setCellValue('E' . $i, $value['up_xiaozhu']?$value['up_xiaozhu']:'/');
            $objExcel->getActiveSheet()->setCellValue('F' . $i, $value['up_name']);
            $objExcel->getActiveSheet()->setCellValue('G' . $i, $value['up_nickname']?$value['up_nickname']:'/');
            $objExcel->getActiveSheet()->setCellValue('H' . $i, $value['up_qq']);
            $objExcel->getActiveSheet()->setCellValue('I' . $i, $value['invite_n']);
            $objExcel->getActiveSheet()->setCellValue('J' . $i, date('Y-m-d H:i-s',$value['atime']));

            $i++;
        }
    }


    protected function addExcelMembership(&$objExcel, $data = [],$ext = []){

        $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);

        $i = 3;
        foreach ($data as $value) {
            /*----------写入内容-------------*/

            switch($value['check']){
                case 1:
                    $status = '待审核';
                    break;
                case 2:
                    $status = '审核通过';
                    break;
                case 3:
                    $status = '审核拒绝';
                    break;
                case 4:
                    $status = '已取消';
                    break;
                default:
                    $status = '--';
                    break;

            }

            switch($value['type']){
                case 3:
                    $type= '申请学员';
                    break;
                case 4:
                    $type = '变更教员';
                    break;
                case 5:
                    $type = '升级成为教员';
                    break;
                case 6:
                    $type = '为学员升星';
                    break;
                default:
                    $type = '--';
                    break;

            }


            $objExcel->getActiveSheet()->setCellValue('A' . $i, $value['name']?$value['name']:'/');
            $objExcel->getActiveSheet()->setCellValue('B' . $i, $value['nickname']?$value['nickname']:'/');
            $objExcel->getActiveSheet()->setCellValue('C' . $i, $value['level']);
            $objExcel->getActiveSheet()->setCellValue('D' . $i, $value['teacher_name']?$value['teacher_name']:'/');
            $objExcel->getActiveSheet()->setCellValue('E' . $i, $value['teacher_nickname']?$value['teacher_nickname']:'/');
            $objExcel->getActiveSheet()->setCellValue('F' . $i, $type);
            $objExcel->getActiveSheet()->setCellValue('G' . $i, $value['atime']?date('Y-m-d H:i-s',$value['atime']):'/');
            $objExcel->getActiveSheet()->setCellValue('H' . $i, $status);
            $objExcel->getActiveSheet()->setCellValue('i' . $i, $value['ouname']?$value['ouname']:'/');
            $objExcel->getActiveSheet()->setCellValue('J' . $i, $value['mtime']?date('Y-m-d H:i-s',$value['mtime']):'/');
            $i++;
        }
    }


    /*
    * 导出共用函数
    */
    protected function excel($data = [], $title_num = [], $title = [], $name = '', $fun = 'addExcelInviteUser',$ext = '')
    {

        vendor('phpexcel.PHPExcel');
        vendor('phpexcel/PHPExcel.IOFactory');
        $objExcel = new \PHPExcel();
        //设置属性

        $objExcel->getProperties()->setCreator('System');
        $objExcel->getProperties()->setLastModifiedBy('System');
        $objExcel->getProperties()->setTitle("excel");
        $objExcel->getProperties()->setSubject("excel");
        $objExcel->getProperties()->setDescription("excel");
        $objExcel->getProperties()->setKeywords("excel");
        $objExcel->getProperties()->setCategory("data");
        $objExcel->setActiveSheetIndex(0);

        //合并单元格

        $objExcel->getActiveSheet()->mergeCells('A1:D1');
        $objExcel->getActiveSheet()->setCellValue('A1', urldecode($name));
        $objExcel->getActiveSheet()->getStyle('A1')->getFill()->getStartColor()->setARGB('red');


        foreach ($title_num as $val) {
            $objExcel->getActiveSheet()->setCellValue($val . '2', $title[$val]);
        }
//         write
        $this->$fun($objExcel, $data);


        // 高置列的宽度
        $width = $ext['WIDTH']?$ext['WIDTH']:20;

        foreach ($title_num as $value) {
            if($ext[$value])
                $width = $ext[$value];
            $objExcel->getActiveSheet()->getColumnDimension($value)->setWidth($width);
        }

        //设置标题填充颜色以及字体居中
        foreach ($title_num as $value) {
            $objExcel->getActiveSheet()->getStyle($value . '1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
            $objExcel->getActiveSheet()->getStyle($value . '1')->getFill()->getStartColor()->setARGB('DCDCDC');

            $objExcel->getActiveSheet()->getStyle($value . '1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }

        $objExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
        $objExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPage &P of &N');

        $objExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        //设置页方向和规模
        $objExcel->getActiveSheet()->getPageSetup()->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
        $objExcel->getActiveSheet()->getPageSetup()->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        $objExcel->setActiveSheetIndex(0);
        $timefmt = date('Y-m-d');
        $name = $name ? $name : 'excel';
        $ex = '2007';
        if ($ex == '2007') { //导出excel2007文档
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $name . '[' . $timefmt . '].xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
            $objWriter->save('php://output');
            exit;
        } else {  //导出excel2003文档
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $name . '[' . $timefmt . '].xls"');
            header('Cache-Control: max-age=0');
            $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
            $objWriter->save('php://output');
            exit;
        }
    }


    public function attachDownload()
    {
        $raw = I('get.')?I('get.'):$this->RxData;

        $ret = [];
        if(!$raw['id']){
            $ret['status'] = E_DATA;
            $ret['errstr'] = 'wrong params';
            goto END;
        }

        $attach_info = $this->model->findAttach('',['id'=>$raw['id']]);
        if(!$attach_info){
            $ret['status'] = E_NOEXIST;
            $ret['errstr'] = 'attach not exists';
            goto END;
        }

        $file_dir = $attach_info['path'];
        \Common\Download_t($file_dir);
END:
        $this->retReturn($ret);
    }


}
