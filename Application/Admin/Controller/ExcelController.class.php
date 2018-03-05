<?php

namespace Admin\Controller;

class DownloadController extends GlobalController
{


    protected $m_m;
    protected $modelUser;
    protected $model;

    public function _initialize($check = true)
    {
        parent::_initialize($check = false);
        $this->modelUser = D('Admin/User');
        $this->model     = D('Admin/Resource');

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

        $data = $this->modelUser->selectUserInvite($column,$where,$limit,$order);

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

        if(!empty($raw['up_uid']) || !empty($raw['up_name']) || !empty($raw['up_nickname']) ){
            $name = urlencode('用户--'.$data[0]['name'] . '--下线列表');
        }else{
            $name = urlencode('【量学小筑】用户关系列表');
        }

        $this->excel($data, $title_num, $title, $name, 'addExcelInviteUser');

    }


    public function addExcelInviteUser(&$objExcel, $data = [],$ext = [])
    {

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


    /*
    * 导出共用函数
    */
    protected function excel($data = [], $title_num = [], $title = [], $name = '', $fun = 'addExcelInviteUser')
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
        foreach ($title_num as $value) {
            $objExcel->getActiveSheet()->getColumnDimension($value)->setWidth(30);
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


        $file_info = $this->model->findFile('role_download',['id'=>$attach_info['pid']]);

        //可阅读该文件的 的角色
        $roles = explode('*',$file_info['role_download']);

        if($file_info['role_download'] == '*-1*' || $this->out['group_id'] == -1)
            goto STP;


        //可下载该文件的 的角色
        if(!(end($roles)))
            array_pop($roles);
        if(!(reset($temp)))
            array_shift($roles);

        //用户的角色
        $user_role_info = D('Admin/AuthRule')->selectAuthGroupAccess('',['uid'=>$this->out['uid']]);

        $group_id = [];
        foreach($user_role_info as $v){
            $group_id[] = $v['group_id'];
        }

        $cd = array_intersect ($roles,$group_id);

        if(!$cd)
            goto END;


STP:

        $file_dir = $attach_info['path'];
//检查文件是否存在
        if (!file_exists( $file_dir)) {
            echo "文件找不到";
            exit ();
        } else {
            //打开文件
            $file = fopen( $file_dir, "r");
            //输入文件标签
            Header("Content-type: application/octet-stream");
            Header("Accept-Ranges: bytes");
            Header("Accept-Length: " . filesize($file_dir));
            Header("Content-Disposition: attachment; filename=" . end(explode('/',$attach_info['path'])));
            //输出文件内容
            //读取文件内容并直接输出到浏览器
            echo fread($file, filesize($file_dir ));
            fclose($file);
            exit ();
        }

        END:
        $this->retReturn($ret);
    }


}