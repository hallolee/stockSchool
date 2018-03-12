<?php
namespace Client\Model;

class MessageModel extends GlobalModel
{

    protected $tableName = TMESSAGE;       //配置表名，默认与模型名相同，若不同，可通过此来进行设置


    public function _initialize()
    {
        parent::_initialize();
    }

    public function addMessage($data)
    {
        $info = M(TMESSAGE)
            ->add($data);

        return $info;
    }

    public function editMessage($data, $where)
    {
        $info = M(TMESSAGE)
            ->where($where)
            ->save($data);

        return $info;
    }

    public function selectMessage($column = '', $where = '', $limit = '', $order = '')
    {

        $res = M(TMESSAGE)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;

    }


    public function findMessage($column = '', $where = '')
    {

        $res = M(TMESSAGE)
            ->field($column)
            ->where($where)
            ->find();

        return $res;

    }


    public function delMessage($where = '')
    {

        $res = M(TMESSAGE)
            ->where($where)
            ->delete();

        return $res;

    }


    public function incMessage($data,$where)
    {
        $info = M(TUSER)
            ->where($where)
            ->setInc( $data['field'],$data['value']);

        return $info;
    }

    public function decMessage($data,$where)
    {
        $info = M(TMESSAGE)
            ->where($where)
            ->setDec( $data['field'],$data['value']);

        return $info;
    }
}