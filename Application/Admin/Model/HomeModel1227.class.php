<?php
namespace Admin\Model;

class HomeModel extends GlobalModel
{

    protected $tableName = TBASIC_INFO;       //配置表名，默认与模型名相同，若不同，可通过此来进行设置


    public function _initialize()
    {
        parent::_initialize();
    }

    public function findAd($column = '', $where = '')
    {
        $info = M(TAD)
            ->field($column)
            ->where($where)
            ->find();

        return $info;
    }


    public function selectAd($column = '', $where = '',$limit='',$order='')
    {
        $info =  M(TAD)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $info;
    }


    public function editAd($data,$where)
    {
        $info =  M(TAD)
            ->where($where)
            ->save($data);

        return $info;
    }


    public function addAd($data)
    {
        $info =  M(TAD)
            ->add($data);

        return $info;
    }


    public function delAd($where)
    {
        $info =  M(TAD)
            ->where($where)
            ->delete();

        return $info;
    }

    public function findSystemInfo($column = '', $where = '')
    {
        $info =  M(TSYSTEM_INFO)
            ->field($column)
            ->where($where)
            ->find();

        return $info;
    }


    public function selectSystemInfo($column = '', $where = '',$limit='',$order='')
    {
        $info =  M(TSYSTEM_INFO)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $info;
    }


    public function saveSystemInfo($data,$where)
    {
        $info =  M(TSYSTEM_INFO)
            ->where($where)
            ->save($data);

        return $info;
    }


    public function addSystemInfo($data)
    {
        $info =  M(TSYSTEM_INFO)
            ->add($data);

        return $info;
    }


    public function editSystemInfoType($data,$where)
    {
        $info =  M(TSYSTEM_INFO_TYPE)
            ->where($where)
            ->save($data);

        return $info;
    }


    public function addSystemInfoType($data)
    {
        $info =  M(TSYSTEM_INFO_TYPE)
            ->add($data);

        return $info;
    }


    public function selectSystemInfoType($column = '', $where = '',$limit='',$order='')
    {
        $info =  M(TSYSTEM_INFO_TYPE)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $info;
    }

    public function findSystemInfoType($column = '', $where = '')
    {
        $info =  M(TSYSTEM_INFO_TYPE)
            ->field($column)
            ->where($where)
            ->find();

        return $info;
    }




    public function selectNav($column='',$where='',$limit='',$sort='')
    {
        $info =  M(TNAV)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($sort)
            ->select();

        return $info;
    }


    public function findNav($column='',$where='',$order = '')
    {
        $info =  M(TNAV)
            ->field($column)
            ->where($where)
            ->order($order)
            ->find();

        return $info;
    }

    public function delNav($where='')
    {
        $info =  M(TNAV)
            ->where($where)
            ->delete();

        return $info;
    }


    public function selectNavWithItem($column='',$where='',$limit='',$sort='')
    {
        $info =  M(TNAV)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN '.TNAV.' b on b.pid = a.id' )
            ->where($where)
            ->limit($limit)
            ->order($sort)
            ->select();

        return $info;
    }

    public function addNav($data)
    {
        $info =  M(TNAV)
            ->add($data);

        return $info;
    }

    public function addAllNav($data)
    {
        $info =  M(TNAV)
            ->addAll($data);

        return $info;
    }

    public function saveNav($data,$where)
    {
        $info =  M(TNAV)
            ->where($where)
            ->save($data);

        return $info;
    }

}