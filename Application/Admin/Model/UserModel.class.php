<?php
namespace Admin\Model;

class UserModel extends GlobalModel
{
    protected $tableName = TUSER; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    public function _initialize()
    {
        parent::_initialize();
    }


    public function findDepartment($column = '', $where = '')
    {
        $re = M(TDEPART)
            ->field($column)
            ->where($where)
            ->find();
        return $re;
    }


    public function selectDepartment($column = '', $where = '',$limit = '',$order = '')
    {
        $re = M(TDEPART)
            ->field($column)
            ->where($where)
            ->select();
        return $re;
    }


    public function addDepartment($data = '')
    {
        $re = M(TDEPART)
            ->add($data);

        return $re;
    }


    public function saveDepartment($where = '', $data = '')
    {

        $re = M(TDEPART)
            ->where($where)
            ->save($data);

        return $re;
    }

    public function findQQList($column = '', $where = '')
    {
        $re = M(TQQLIST)
            ->field($column)
            ->where($where)
            ->find();
        return $re;
    }


    public function selectQQList($column = '', $where = '',$limit = '',$order = '')
    {
        $re = M(TQQLIST)
            ->field($column)
            ->where($where)
            ->select();
        return $re;
    }



    public function selectQQListWithInfo($column = '', $where = '',$limit = '',$order = '')
    {
        $re = M(TQQLIST)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN '.TDEPART.' b on b.id = a.department_id')
            ->where($where)
            ->select();
        return $re;
    }

    public function selectUser($column = '', $where = '',$limit = '',$order = '')
    {
        $re = M(TUSER)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TUSER_LEVEL.' c on c.id = a.level_id')
            ->join('LEFT JOIN '.TUSER.' e on e.uid = a.teacher_uid')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();
        return $re;
    }


    public function selectUserInvite($column = '', $where = '',$limit = '',$order = '')
    {


        $data['re'] = M(TUSER)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN '.TREFER.' b on a.uid = b.uid')
            ->join('LEFT JOIN '.TUSER.' d on d.uid = b.upline')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        $data['total'] = M(TUSER)
            ->alias('a')
            ->field('count(*) total')
            ->join('LEFT JOIN '.TREFER.' b on a.uid = b.uid')
            ->join('LEFT JOIN '.TUSER.' d on d.uid = b.upline')
            ->where($where)
            ->select();
        return $data ;
    }




    public function addQQList($data = '')
    {
        $re = M(TQQLIST)
            ->add($data);

        return $re;
    }


    public function saveQQList($where = '', $data = '')
    {

        $re = M(TQQLIST)
            ->where($where)
            ->save($data);

        return $re;
    }

    public function saveQuestion( $data = '',$where = '')
    {

        $re = M(TUSER_Q)
            ->where($where)
            ->save($data);

        return $re;
    }

    public function selectQuestion($column = '', $where = '',$limit = '',$order = '')
    {
        $re = M(TUSER_Q)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();
        return $re;
    }

    public function addQuestion( $data = '')
    {

        $re = M(TUSER_Q)
            ->add($data);

        return $re;
    }

}