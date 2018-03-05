<?php
namespace Client\Model;

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


    public function selectCat($column = '', $where = '', $limit = '', $order = '')
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
            ->join('LEFT JOIN '.TUSER_T.' b on b.uid = a.teacher_uid ' )
            ->join('LEFT JOIN '.TUSER_LEVEL.' c on a.level_id = c.id ' )
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $info;
    }


    public function selectCourseOnly($column = '', $where = '', $limit = '', $order = '')
    {
        $info = M(TCOURSE)
            ->field($column)
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
            ->join('LEFT JOIN '.TUSER_T.' b on a.teacher_uid = b.uid ' )
            ->join('LEFT JOIN '.TUSER_LEVEL.' c on a.level_id = c.id ' )
            ->join('LEFT JOIN '.TCOURSE_C.' d on a.cat_id = d.id ' )
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
    public function addCat($data)
    {
        $info = M(TCOURSE_C)
            ->add($data);

        return $info;
    }

    public function editCat($data,$where)
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

    public function delCat($where)
    {
        $info = M(TCOURSE_C)
            ->where($where)
            ->delete();

        return $info;
    }


}
