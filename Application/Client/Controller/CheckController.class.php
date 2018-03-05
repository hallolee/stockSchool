<?php
namespace Client\Controller;

class CheckController extends GlobalController
{
    protected $model;
    protected $m_m;
    protected $m_m1;

    public function _initialize($check = true)
    {

        parent::_initialize($check);
        $this->model = D('Client/Check');

    }

    /**
     * @param $data
     * @param $type $type 1、add ;2、modify
     * @return bool
     */
    public function addCheck($data,$type){

        $ret = false;
        if(!$data || !$type){
            goto END;
        }
        $t_check = $this->model->addCheck($data['data']);
        $ext = [];
        foreach($data['data_ext'] as &$v){
            $v['pid'] = $t_check;
            $ext[]= $v;
        }

        $t_check_ext = $this->model->addCheckExt($data['data_ext']);

        if(!$t_check || !$t_check_ext)
            goto END;

        $ret = $t_check;

END:
        return $ret;

    }

}
