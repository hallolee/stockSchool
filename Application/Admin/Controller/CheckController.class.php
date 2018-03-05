<?php
namespace Admin\Controller;

class CheckController extends GlobalController{

    protected $m_m1;
    protected $model;
    protected $modelProfile;
    protected $modelClassroom;

    public function _initialize($check = true)      {

        parent::_initialize($check);
        $this->model = D('Admin/Check');
        $this->modelClassroom = D('Admin/Classroom');
        $this->modelProfile   = D('Client/Profile');

    }

    /**
     * @param $data
     * @param $type $type 1eq add ;2eq modify
     * @return bool
     */
    public function addCheck($data,$type){

        $ret = false;
        if(!$data || !$type){
            goto END;
        }
        $t_check = $this->model->addCheck($data['data']);
        $data['data_ext']['pid'] = $t_check;
        $t_check_ext = $this->model->addCheckExt($data['data_ext']);

        if(!$t_check || !$t_check_ext)
            goto END;

        $ret = $t_check;

END:
        return $ret;

    }


    public function check(){

        $ret = ['status'=>E_SYSTEM,'errstr'=>''];
        $raw = $this->RxData;

        $this->model->startTrans();
        if(!$raw['id'] || empty($raw['status'])){
            $ret = ['status'=>E_DATA,'errstr'=>'wrong status'];
            goto END;
        }

        if(!in_array($raw['status'],[2,3])){
            $ret = ['status'=>E_DATA,'errstr'=>'wrong params status'];
            goto END;
        }
        if($raw['status'] == 3 && empty($raw['reason'])){
            $ret = ['status'=>E_NOTENOUGH,'errstr'=>'reason can not be none'];
            goto END;
        }

        $check_info = $this->model->selectCheckWithExt(
            'a.*,
            b.id,b.data,b.table,b.tid,b.condition,update',
            ['a.id'=>$raw['id'],'b.status'=>1]
        );


        if(!$check_info){
            $ret = ['status'=>E_CHECKED,'errstr'=>'data has checked or not exists'];
            goto END;
        }
//只能审核一次
        if($check_info[0]['once'] == 1 && $check_info[0]['status'] != LINE){
            $ret = ['status'=>E_CHECKED,'errstr'=>'data has checked'];
            goto END;
        }

        $check_data = [
            'status' => $raw['status'],
            'reason' => $raw['reason']?$raw['reason']:'',
            'mtime'  => time(),
            'o_uid'  => $this->out['uid']
        ];
        $check_table = $this->model->editCheck($check_data,['id'=>$raw['id']]);
        $t_data = [
            'check'    =>  $raw['status'],
            'mtime'    =>  time(),
            'reason'   =>  $raw['reason']?$raw['reason']:'',

        ];

        foreach($check_info as $v){
            $t_data_temp = $t_data;

            if($v['condition']){
                $where = unserialize($v['condition']);
            }else{
                $where = ['id'=>$v['tid']];
            }
            if($raw['status'] == PASS ){
                if($v['tid'] == C_BACK_STATUS)
                    continue;
                if($v['tid'] == C_QUIT_TE){
                    $res1 = D('Admin/System')->delRoleGroupUser($where);
                    continue;
                }
                if($v['update'] == 1){
                    if($v['data']){
                        $t_data_temp = array_merge($t_data,unserialize($v['data']));
                    }
                }else{
                    if($v['data']){
                        $t_data_temp = unserialize($v['data']);
                    }
                }

            }else{
                //还原教员名额
                if ($v['table'] == TUSER_T && $v['tid'] &&  empty($v['data']) && $v['condition']) {
                    if($v['tid'] == C_INC_QUOTA){
                        $check_t_table = $this->modelClassroom->decTeacher(
                            ['column' => 'quota_left', 'value' => 1],
                            unserialize($v['condition']));
                    }elseif($v['tid'] == C_DEC_QUOTA ){
                        $check_t_table = $this->modelClassroom->incTeacher(
                            ['column' => 'quota_left', 'value' => 1],
                            unserialize($v['condition']));
                    }
                    continue;
                }

                if($v['update'] != C_UPDATE)
                    continue;

                if($v['tid'] == C_BACK_STATUS)
                    $t_data_temp = unserialize($v['data']);

            }

            $table = $v['table'];
            $check_t_table = M( $table )->where($where)->save($t_data_temp);
            unset($t_data_temp);
        }

        if(!$check_table ){
            $this->model->rollback();
            goto END;

        }

        $this->model->commit();
        $ret['status'] = E_OK;

END:
        $this->retReturn($ret);

    }



}