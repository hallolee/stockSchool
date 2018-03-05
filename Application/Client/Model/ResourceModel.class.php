<?php
namespace Client\Model;

class ResourceModel extends GlobalModel
{

    protected $tableName = TFILE;       //配置表名，默认与模型名相同，若不同，可通过此来进行设置


    public function _initialize()
    {
        parent::_initialize();
    }

    public function addFile($data)
    {
        $info = M(TFILE)
            ->add($data);

        return $info;
    }

    public function editFile($data,$where)
    {
        $info = M(TFILE)
            ->where($where)
            ->save($data);

        return $info;
    }

    public function incFile($data,$where)
    {
        $info = M(TFILE)
            ->where($where)
            ->setInc( $data['field'],$data['value']);

        return $info;
    }

    public function selectFile($column = '',$where = '',$limit = '',$order = ''){

        $res = M(TFILE)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;

    }


    public function findFile($column = '',$where = '',$order = ''){

        $res = M(TFILE)
            ->field($column)
            ->where($where)
            ->find();

        return $res;

    }



    public function findFileWithUid($column = '',$where = '',$order = ''){

        $res = M(TFILE)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN '.TUSER.' b on b.uid = a.uid ')
            ->where($where)
            ->find();

        return $res;

    }



    public function delFile($where)
    {
        $info = M(TFILE)
            ->where($where)
            ->delete();

        return $info;
    }

    public function selectAttach($column = '',$where = '',$limit = '',$order = ''){

        $res = M(TFILE_ATTACH)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;

    }

    public function findAttach($column = '',$where = ''){

        $res = M(TFILE_ATTACH)
            ->field($column)
            ->where($where)
            ->find();

        return $res;

    }

    public function editAttach($data = '',$where = ''){

        $res = M(TFILE_ATTACH)
            ->where($where)
            ->save($data);

        return $res;

    }

    public function delAttach($where = ''){

        $res = M(TFILE_ATTACH)
            ->where($where)
            ->delete();

        return $res;

    }


    public function addAttach($data)
    {
        $info = M(TFILE_ATTACH)
            ->add($data);

        return $info;
    }



    // 评论
    public function findReply( $column='', $where='' ){
        $re = M( TFILE_REVIEW )
            ->field( $column )
            ->where( $where )
            ->find();

        return $re;
    }

    public function selectReply( $column='', $where='',  $limit='' ,$order='' ){
        $re = M( TFILE_REVIEW )
            ->field( $column )
            ->where( $where )
            ->limit( $limit )
            ->order( $order )
            ->select();

        return $re;
    }

    public function addReply( $data='' ){
        $re = M( TFILE_REVIEW )
            ->add( $data );

        return $re;
    }

    public function editReply(  $data='',$where='' ){
        $re = M( TFILE_REVIEW )
            ->where( $where )
            ->save( $data );

        return $re;
    }

    public function selectKeys($column = '',$where = '',$limit = '',$order = ''){

        $res = M(TFILE_V_K)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;

    }
    public function selectKeysWithVideo($column = '',$where = '',$limit = '',$order = ''){

        $res = M(TFILE_V_K)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TUSER.' b on b.uid =a.uid')
            ->join('LEFT JOIN '.TFILE.' c on c.id =a.video_id')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;

    }

    public function selectKeysWithUid($column = '',$where = '',$limit = '',$order = ''){

        $res = M(TFILE_V_K)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TUSER.' b on b.uid =a.uid')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;

    }

    public function addKey($data){

        $res = M(TFILE_V_K)
            ->add($data);

        return $res;

    }

    public function saveKey($data,$where){

        $res = M(TFILE_V_K)
            ->where($where)
            ->save($data);

        return $res;

    }


}
