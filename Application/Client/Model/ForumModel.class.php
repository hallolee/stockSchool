<?php
namespace Client\Model;

class ForumModel extends GlobalModel
{

    protected $tableName = TFILE;       //配置表名，默认与模型名相同，若不同，可通过此来进行设置


    public function findReply( $column='', $where='' ){
        $re = M( TFORUM_R )
            ->field( $column )
            ->where( $where )
            ->find();

        return $re;
    }


    public function selectReply( $column='', $where='',  $limit='' ,$order='' ){
        $re = M( TFORUM_R )
            ->field( $column )
            ->where( $where )
            ->limit( $limit )
            ->order( $order )
            ->select();

        return $re;
    }

    public function selectReplyWithUid( $column='', $where='',  $limit='' ,$order='' ){
        $re = M( TFORUM_R )
            ->alias('a')
            ->field( $column )
            ->join('LEFT JOIN '.TUSER.' b on b.uid = a.uid')
            ->where( $where )
            ->limit( $limit )
            ->order( $order )
            ->select();

        return $re;
    }

    public function addReply( $data='' ){
        $re = M( TFORUM_R )
            ->add( $data );

        return $re;
    }

    public function editReply(  $data='',$where='' ){
        $re = M( TFORUM_R )
            ->where( $where )
            ->save( $data );

        return $re;
    }


    public function selectForum( $column='', $where='',  $limit='' ,$order='' ){
        $re = M( TFORUM )
            ->alias('a')
            ->field( $column )
            ->join('LEFT JOIN '.TUSER.' b on b.uid = a.uid')
            ->where( $where )
            ->limit( $limit )
            ->order( $order )
            ->select();

        return $re;
    }


    public function delForum( $where='' ){

        $re = M( TFORUM )
            ->where( $where )
            ->delete();

        return $re;
    }


    public function selectForumWithCheck( $column='', $where='',  $limit='' ,$order='' ){
        $re = M( TFORUM )
            ->alias('a')
            ->field( $column )
            ->join('INNER JOIN '.TCHECK_E.' c on c.tid = a.id and c.status = 1 ')
            ->where( $where )
            ->limit( $limit )
            ->order( $order )
            ->select();

        return $re;
    }


    public function findForumOnly( $column='', $where=''){
        $re = M( TFORUM )
            ->field( $column )
            ->where( $where )
            ->find();

        return $re;
    }

    public function findForum( $column='', $where=''){
        $re = M( TFORUM )
            ->alias('a')
            ->field( $column )
            ->join('LEFT JOIN '.TUSER.' b on b.uid = a.uid')
            ->join('LEFT JOIN '.TUSER_LEVEL.' c on c.id = b.level_id')
            ->where( $where )
            ->find();

        return $re;
    }

    public function addForum( $data='' ){
        $re = M( TFORUM )
            ->add( $data );

        return $re;
    }

    public function editForum(  $data='',$where='' ){
        $re = M( TFORUM )
            ->where( $where )
            ->save( $data );

        return $re;
    }


    public function incForum($data,$where)
    {
        $info = M(TFORUM)
            ->where($where)
            ->setInc( $data['field'],$data['value']);

        return $info;
    }

    public function selectHomework( $column='', $where='',  $limit='' ,$order='' ){
        $re = M( TFORUM_C )
            ->alias('a')
            ->field( $column )
            ->join('LEFT JOIN '.TUSER.' b on b.uid = a.uid')
            ->where( $where )
            ->limit( $limit )
            ->order( $order )
            ->select();

        return $re;
    }


    public function selectHomeworkStudent( $column='', $where='',  $limit='' ,$order='' ){
        $re = M( TFORUM_C_R )
            ->alias('a')
            ->field( $column )
            ->join('LEFT JOIN '.TFORUM_C.' b on b.uid = a.uid')
            ->where( $where )
            ->limit( $limit )
            ->order( $order )
            ->select();

        return $re;
    }

    public function findHomework( $column='', $where='',  $limit='' ,$order='' ){
        $re = M( TFORUM_C )
            ->alias('a')
            ->field( $column )
            ->join('LEFT JOIN '.TUSER.' b on b.uid = a.uid')
            ->join('LEFT JOIN '.TUSER_LEVEL.' c on c.id = b.level_id')
            ->where( $where )
            ->find();

        return $re;
    }

    public function addHomework( $data='' ){
        $re = M( TFORUM_C )
            ->add( $data );

        return $re;
    }

    public function editHomework(  $data='',$where='' ){
        $re = M( TFORUM_C )
            ->where( $where )
            ->save( $data );

        return $re;
    }


    public function incHomework($data,$where)
    {
        $info = M(TFORUM_C)
            ->where($where)
            ->setInc( $data['field'],$data['value']);

        return $info;
    }


    public function addAttach($data)
    {
        $re = M( TFORUM_A )
            ->add( $data );


        return $re;
    }


    public function findAttach($column = '',$where = '')
    {
        $re = M( TFORUM_A )
            ->field($column)
            ->where( $where )
            ->find()        ;


        return $re;
    }


    public function editAttach($data,$where)
    {
        $re = M( TFORUM_A )
            ->where( $where )
            ->save( $data );


        return $re;
    }

    public function delAttach($where)
    {
        $re = M( TFORUM_A )
            ->where( $where )
            ->delete( );


        return $re;
    }



}