<?php
namespace Admin\Model;

class ClassroomModel extends GlobalModel
{

    protected $tableName = TBASIC_INFO;       //配置表名，默认与模型名相同，若不同，可通过此来进行设置


    public function _initialize()
    {
        parent::_initialize();
    }

    public function selectTeacher($column = '', $where = '', $limit = '', $order = '')
    {
        $info = M(TUSER_T)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TUSER.' b on a.uid = b.uid ' )
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $info;
    }

    public function editTeacher($data = '', $where = '')
    {
        $info = M(TUSER_T)
            ->where($where)
            ->save($data);

        return $info;
    }


    public function addTeacher($data = '')
    {
        $info = M(TUSER_T)
            ->add($data);

        return $info;
    }


    public function selectCate($column = '', $where = '', $limit = '', $order = '')
    {
        $info = M(TCOURSE_C)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $info;
    }


    public function selectCourse($column = '', $where = '', $limit = '', $order = '')
    {
        $info = M(TCOURSE)
            ->alias('a')
            ->field($column)
            //->join('LEFT JOIN '.TCOURSE_C.' b on a.cate_id = b.id ' )
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $info;
    }


    public function findCourse($column = '', $where = '', $limit = '', $order = '')
    {
        $info = M(TCOURSE)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN '.TUSER_LEVEL.' b on a.level_id = b.id ' )
            ->join('LEFT JOIN '.TUSER_T.' c on a.teacher_uid = c.uid ' )
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->find();

        return $info;
    }



    public function addCourse($data)
    {
        $info = M(TCOURSE)
            ->add($data);

        return $info;
    }
    public function addCate($data)
    {
        $info = M(TCOURSE_C)
            ->add($data);

        return $info;
    }

    public function editCate($data,$where)
    {
        $info = M(TCOURSE_C)
            ->where($where)
            ->save($data);

        return $info;
    }


    public function editCourse($data,$where)
    {
        $info = M(TCOURSE)
            ->where($where)
            ->save($data);

        return $info;
    }

    public function delCate($where)
    {
        $info = M(TCOURSE_C)
            ->where($where)
            ->delete();

        return $info;
    }

    public function decTeacher($data,$where)
    {
        $info = M(TUSER_T)
            ->where($where)
            ->setDec($data['column'],$data['value']);

        return $info;
    }

    public function incTeacher($data,$where)
    {
        $info = M(TUSER_T)
            ->where($where)
            ->setInc($data['column'],$data['value']);

        return $info;
    }


}
