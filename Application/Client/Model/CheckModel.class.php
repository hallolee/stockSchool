<?php
namespace Client\Model;

class CheckModel extends GlobalModel
{

    protected $tableName = TCHECK;       //配置表名，默认与模型名相同，若不同，可通过此来进行设置


    public function _initialize()
    {
        parent::_initialize();
    }

    public function addCheck($data)
    {
        $info = M(TCHECK)
            ->add($data);

        return $info;
    }

    public function editCheck($data, $where)
    {
        $info = M(TCHECK)
            ->where($where)
            ->save($data);

        return $info;
    }

    public function selectCheck($column = '', $where = '', $limit = '', $order = '')
    {

        $res = M(TCHECK)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;

    }

    public function selectCheckWithExt($column = '', $where = '', $limit = '', $order = '')
    {
        $res = M(TCHECK)
            ->alias('a')
            ->field($column)
            ->join( 'LEFT JOIN '.TCHECK_E.' b on a.id = b.pid ' )
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;

    }


    public function findChk($column = '', $where = '', $order = '')
    {
        $res = M(TCHECK)
            ->field($column)
            ->where($where)
            ->find();

        return $res;

    }


    public function findChkWithExt($column = '', $where = '')
    {

        $res = M(TCHECK)
            ->alias('a')
            ->field($column)
            ->join( 'LEFT JOIN '.TCHECK_E.' b on a.id = b.pid ' )
            ->where($where)
            ->find();

        return $res;

    }


    public function delCheck($where)
    {
        $info = M(TCHECK)
            ->where($where)
            ->delete();

        return $info;
    }

    public function addCheckExt($data)
    {
        $info = M(TCHECK_E)
            ->addAll($data);

        return $info;
    }

    public function editCheckExt($data, $where)
    {
        $info = M(TCHECK_E)
            ->where($where)
            ->save($data);

        return $info;
    }

    public function selectCheckExt($column = '', $where = '', $limit = '', $order = '')
    {

        $res = M(TCHECK_E)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;

    }


    public function delCheckExt($where)
    {
        $info = M(TCHECK_E)
            ->where($where)
            ->delete();

        return $info;
    }


    public function findChkExt($column = '', $where = '', $order = '')
    {

        $res = M(TCHECK_E)
            ->field($column)
            ->where($where)
            ->find();

        return $res;

    }

}