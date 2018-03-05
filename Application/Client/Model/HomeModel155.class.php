<?php
namespace Client\Model;

class HomeModel extends GlobalModel
{

    protected $tableName = TBASIC_INFO;       //配置表名，默认与模型名相同，若不同，可通过此来进行设置


    public function _initialize()
    {
        parent::_initialize();
    }

    public function findAd($column = '', $where = '')
    {
        $res = M(TAD)
            ->field($column)
            ->where($where)
            ->find();

        return $res;
    }

    public function selectBasic($column = '', $where = '',$limit='',$order='')
    {
        $res = M(TBASIC_INFO)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }


    public function selectAd($column = '', $where = '',$limit='',$order='')
    {
        $res =  M(TAD)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }


    public function editAd($data,$where)
    {
        $re =  M(TAD)
            ->where($where)
            ->save($data);

        return $re;
    }


    public function addAd($data)
    {
        $re =  M(TAD)
            ->add($data);

        return $re;
    }

    public function findSystemInfo($column = '', $where = '',$order = '')
    {
        $res =  M(TSYSTEM_INFO)
            ->field($column)
            ->where($where)
            ->order($order)
            ->find();

        return $res;
    }

    public function findSystemInfoWithItem($column = '', $where = '',$order = '')
    {
        $res =  M(TSYSTEM_INFO)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN '.TSYSTEM_INFO_TYPE.' b on b.id = a.type')
            ->where($where)
            ->order($order)
            ->find();

        return $res;
    }


    public function findSystemTypeInfo($column = '', $where = '',$order = '')
    {
        $res =  M(TSYSTEM_INFO_TYPE)
            ->field($column)
            ->where($where)
            ->order($order)
            ->find();

        return $res;
    }

    public function incSystemInfo($where,$data)
    {
        $re =  M(TSYSTEM_INFO)
            ->where($where)
            ->setInc($data['column'],$data['value']);

        return $re;
    }


    public function selectSystemInfo($column = '', $where = '',$limit = '',$order = '')
    {
        $res =  M(TSYSTEM_INFO)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }

    public function selectSystemInfoWith($column = '', $where = '',$limit = '',$order = '')
    {
        $res =  M(TSYSTEM_INFO)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN '.TUSER_LEVEL.' b on b.id =a.level_id' )
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }

    public function saveSystemInfo($data,$where)
    {
        $re =  M(TSYSTEM_INFO)
            ->where($where)
            ->save($data);

        return $re;
    }

    public function addSystemInfo($data)
    {
        $re =  M(TSYSTEM_INFO)
            ->add($data);

        return $re;
    }

    public function selectNav($column='',$where='',$limit='',$order='')
    {
        $res =  M(TNAV)
            ->alias('a')
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }

    public function findSystemType($column='',$where='',$limit='',$order='')
    {
        $res =  M(TSYSTEM_INFO_TYPE)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->find();

        return $res;
    }

    public function selectNavTop($column='',$where='',$limit='',$order='')
    {
        $res =  M(TNAV)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }

    public function delNav($where='')
    {
        $res =  M(TNAV)
            ->where($where)
            ->delete();

        return $res;
    }

    public function selectNavItem($column='',$where='',$limit='',$order='')
    {
        $res =  M(TNAV)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TSYSTEM_INFO_TYPE.' c on a.item_id =c.id and c.status=1')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }

    public function selectNavItemTop($column='',$where='',$limit='',$order='')
    {
        $res =  M(TNAV)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN '.TSYSTEM_INFO_TYPE.' c on a.item_id =c.id and c.status=1')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }

    public function addNav($data)
    {
        $re =  M(TNAV)
            ->add($data);

        return $re;
    }

}