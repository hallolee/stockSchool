<?php
namespace Admin\Model;

class BasicInfoModel extends GlobalModel
{

    protected $tableName = TBASIC_INFO;       //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    protected $product_ext;

    public function _initialize()
    {
        parent::_initialize();       
    }

    public function BasicSet($data, $where)
    {

        $re = $this
            ->where($where)
            ->save($data);

        return $re;
    }

    public function BasicShow( $where )
    {
        $re = $this
            ->field('field,value')
            ->where( $where )
            ->select();

        return $re;
    }


    public function BasicFind( $where )
    {
        $re = $this
            ->field('value')
            ->where( $where )
            ->find();

        return $re;
    }

}