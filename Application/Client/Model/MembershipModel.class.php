<?php
namespace Client\Model;

class MembershipModel extends GlobalModel
{

    protected $tableName = TUSER;       //配置表名，默认与模型名相同，若不同，可通过此来进行设置


    public function _initialize()
    {
        parent::_initialize();
    }


    public function addRecordUpgrade($data)
    {
        $re = M( TUSER_UPGRADE_REC )
            ->add( $data );

        return $re;
    }



    public function editRecordUpgrade($data,$where)
    {
        $res = M( TUSER_UPGRADE_REC )

            ->where($where)
            ->save($data);

        return $res;
    }

    public function findRecordUpgrade($column = '', $where = '')
    {
        $res = M( TUSER_UPGRADE_REC )
            ->field($column)
            ->where($where)
            ->order('id desc')
            ->find();

        return $res;
    }


    public function selectRecordUpgrade($column = '', $where = '', $limit = '', $order = '')
    {
        $res = M( TUSER_UPGRADE_REC )
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN '. TUSER . " b on b.uid = a.uid")
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }

    public function selectRecordUpgradeOnly($column = '', $where = '', $limit = '', $order = '')
    {
        $res = M( TUSER_UPGRADE_REC )
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }



    public function addRecordChgTea($data)
    {
        $re = M( TUSER_CHGTEA_REC )
            ->add( $data );

        return $re;
    }



    public function editRecordChgTea($data,$where)
    {
        $res = M( TUSER_CHGTEA_REC )

            ->where($where)
            ->save($data);

        return $res;
    }

    public function findRecordChgTea($column = '', $where = '',$order = '')
    {
        $res = M( TUSER_CHGTEA_REC )
            ->field($column)
            ->where($where)
            ->order($order)
            ->find();

        return $res;
    }


    public function selectRecordChgTea($column = '', $where = '', $limit = '', $order = '')
    {
        $res = M( TUSER_CHGTEA_REC )
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }



    public function addAttach($data)
    {
        $re = M( TUSER_APPLY_ATT)
            ->add( $data );


        return $re;
    }


    public function editAttach($data,$where)
    {
        $re = M( TUSER_APPLY_ATT)
            ->where( $where )
            ->save( $data );


        return $re;
    }


    public function findAttach($column = '',$where = '')
    {
        $re = M( TUSER_APPLY_ATT )
            ->field($column)
            ->where( $where )
            ->find()        ;


        return $re;
    }

    public function selectAttach($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M( TUSER_APPLY_ATT )
            ->field($column)
            ->where( $where )
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function addRecord($data)
    {
        $res = M(TUSER_APPLY_REC)
            ->add($data);

        return $res;
    }

    public function findRecord($column,$where)
    {
        $res = M(TUSER_APPLY_REC)
            ->field($column)
            ->where($where)
            ->find();

        return $res;
    }

    public function editRecord($data,$where)
    {
        $res = M(TUSER_APPLY_REC)
            ->where($where)
            ->save($data);

        return $res;
    }

    public function selectRecord($column = '', $where = '', $limit = '', $order = '')
    {
        $res = M(TUSER_APPLY_REC)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }




    public function addRecordTea($data)
    {
        $res = M(TUSER_APPLY_TEA_REC)
            ->add($data);

        return $res;
    }

    public function findRecordTea($column,$where)
    {
        $res = M(TUSER_APPLY_TEA_REC)
            ->field($column)
            ->where($where)
            ->find();

        return $res;
    }

    public function editRecordTea($data,$where)
    {
        $res = M(TUSER_APPLY_TEA_REC)
            ->where($where)
            ->save($data);

        return $res;
    }

    public function selectRecordTea($column = '', $where = '', $limit = '', $order = '')
    {
        $res = M(TUSER_APPLY_TEA_REC)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }





    public function addRecordChgStatus($data)
    {
        $res = M(TUSER_CHG_STATUS_REC)
            ->add($data);

        return $res;
    }

    public function findRecordChgStatus($column,$where)
    {
        $res = M(TUSER_CHG_STATUS_REC)
            ->field($column)
            ->where($where)
            ->order('id desc')
            ->find();

        return $res;
    }

    public function editRecordChgStatus($data,$where)
    {
        $res = M(TUSER_CHG_STATUS_REC)
            ->where($where)
            ->save($data);

        return $res;
    }

    public function selectRecordChgStatus($column = '', $where = '', $limit = '', $order = '')
    {
        $res = M(TUSER_CHG_STATUS_REC)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }


    public function selectTeacher($column = '', $where = '', $limit = '', $order = '')
    {
        $res = M(TUSER_T)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TUSER.' b on b.uid =a.uid')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }

    public function findTeacher($column = '', $where = '',$limit = '',$order = '')
    {
        $res = M(TUSER_T)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN '.TUSER.' b on b.uid =a.uid')
            ->where($where)
            ->order($order)
            ->find();

        return $res;
    }



    public function delAttach($where)
    {
        $re = M( TUSER_APPLY_ATT )
            ->where( $where )
            ->delete( );


        return $re;
    }



}