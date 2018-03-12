<?php
namespace Admin\Model;

class SystemModel extends GlobalModel
{

    protected $tableName = TBASIC_INFO;       //配置表名，默认与模型名相同，若不同，可通过此来进行设置


    public function _initialize()
    {
        parent::_initialize();
    }

    public function findBasic($column = '', $where = '')
    {
        $info = M(TBASIC_INFO)
            ->field($column)
            ->where($where)
            ->find();

        return $info;
    }


    public function selectBasic($column = '', $where = '',$limit='',$order='')
    {
        $info = M(TBASIC_INFO)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $info;
    }


    public function editBasic($data,$where)
    {
        $info = M(TBASIC_INFO)
            ->where($where)
            ->save($data);

        return $info;
    }



    public function selectRoleGroup($column = '', $where = '',$limit = '',$order = '')
    {
        $re = M(TROLE)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();
        return $re;
    }

    public function findRoleGroup($column = '', $where = '')
    {
        $re = M(TROLE)
            ->field($column)
            ->where($where)
            ->find();
        return $re;
    }


    public function selectUserRoleGroup($column = '', $where = '',$limit = '',$order = '')
    {
        $re = M(TROLE_USER)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TROLE.' b on b.id = a.group_id and b.status != 2')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();
        return $re;
    }

    public function editRoleGroup( $data = '',$where = '')
    {

        $re = M(TROLE)
            ->where($where)
            ->save($data);

        return $re;
    }


    public function addRoleGroup( $data = '')
    {

        $re = M(TROLE)
            ->add($data);

        return $re;
    }
    public function addRoleGroupUserAll( $data = '')
    {

        $re = M(TROLE_USER)
            ->addAll($data);

        return $re;
    }



    public function findUserRole( $column = '',$where = '')
    {

        $re = M(TROLE_USER)
            ->field($column)
            ->where($where)
            ->find();

        return $re;
    }

    public function selectUserRole( $column = '',$where = '')
    {

        $re = M(TROLE_USER)
            ->field($column)
            ->where($where)
            ->select();

        return $re;
    }

    public function delRoleGroupUser( $where = '')
    {

        $re = M(TROLE_USER)
            ->where($where)
            ->delete();

        return $re;
    }


    public function saveRoleGroup( $data = '',$where = '')
    {

        $re = M(TROLE)
            ->where($where)
            ->save($data);

        return $re;
    }


    public function addUserRole( $data = '')
    {

        $re = M(TROLE_USER)
            ->add($data);

        return $re;
    }

    public function saveUserRole( $data = '',$where='')
    {

        $re = M(TROLE_USER)
            ->where($where)
            ->save($data);

        return $re;
    }

    public function delRoleGroup( $where = '')
    {

        $re = M(TROLE)
            ->where($where)
            ->delete();

        return $re;
    }

    public function delUserRole( $where = '')
    {

        $re = M(TROLE_USER)
            ->where($where)
            ->delete();

        return $re;
    }

    public function selectLevel( $column='', $where='' ) {
        $re = M(TUSER_LEVEL)
            ->field( $column )
            ->where( $where )
            ->select();
        return $re;
    }

    public function findLevel( $column='', $where='' ) {
        $re = M(TUSER_LEVEL)
            ->field( $column )
            ->where( $where )
            ->order('id desc')
            ->find();
        return $re;
    }


    public function addLevel( $data='' ) {
        $re = M(TUSER_LEVEL)
            ->add($data);
        return $re;
    }


    public function editLevel( $data='', $where='' ) {
        $re = M(TUSER_LEVEL)
            ->where( $where )
            ->save($data);
        return $re;
    }

}