<?php
namespace Admin\Model;

class HomeworkModel extends GlobalModel
{

    protected $tableName = THWK;       //配置表名，默认与模型名相同，若不同，可通过此来进行设置


    public function _initialize()
    {
        parent::_initialize();
    }


    public function addAttach($data)
    {
        $re = M( THWK_A )
            ->add( $data );

        return $re;
    }

    public function addHomework($data)
    {
        $res = M(THWK)
            ->add($data);

        return $res;
    }

    public function editHomework($data,$where)
    {
        $res = M(THWK)
            ->where($where)
            ->save($data);

        return $res;
    }

    public function findHomeworkWithUid($column = '', $where = '')
    {
        $res = M(THWK)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN ' . TUSER . ' b on b.uid = a.uid')
            ->join('LEFT JOIN ' . TUSER_LEVEL . ' c on c.id = a.level_id')
            ->where($where)
            ->find();

        return $res;
    }


    public function findHomeworkOnly($column = '', $where = '')
    {
        $res = M(THWK)
            ->field($column)
            ->where($where)
            ->find();

        return $res;
    }



    public function selectHomework($column = '', $where = '', $limit = '', $order = '')
    {
        $res = M(THWK)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN ' . TCHECK . ' c on c.id = a.check_id')
            ->join('LEFT JOIN ' . TCHECK_E . ' d on d.tid = a.id and d.status=1')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }

    public function selectHomeworkForAnalysis($column = '', $where = '', $limit = '', $order = '')
    {
        $res = M(THWK)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN ' . TUSER . ' b on b.uid = a.uid')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }

    public function selectHomeworkOnly($column = '', $where = '', $limit = '', $order = '')
    {
        $res = M(THWK)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }

    public function selectHomeworkOnly1($column = '', $where = '', $limit = '', $order = '')
    {
        $res = M(THWK)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }

    public function editAttach($data,$where)
    {
        $re = M( THWK_A)
            ->where( $where )
            ->save( $data );


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

    public function selectAttach($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M( TFORUM_A )
            ->field($column)
            ->where( $where )
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function addHomeworkRecord($data)
    {
        $res = M(THWK_RECORD)
            ->add($data);

        return $res;
    }

    public function editHomeworkRecord($data='',$where)
    {
        $res = M(THWK_RECORD)
            ->where($where)
            ->save($data);

        return $res;
    }


    public function findReply( $column='', $where='' ){
        $re = M( THWK_R )
            ->field( $column )
            ->where( $where )
            ->find();

        return $re;
    }



    public function findReplyWithUid( $column='', $where='' ){
        $re = M( THWK_R )
            ->alias('a')
            ->field( $column )
            ->join('INNER JOIN '.TUSER.' b on b.uid a.uid')
            ->where( $where )
            ->find();

        return $re;
    }


    public function selectReply( $column='', $where='',  $limit='' ,$order='' ){
        $re = M( THWK_R )
            ->field( $column )
            ->where( $where )
            ->limit( $limit )
            ->order( $order )
            ->select();

        return $re;
    }


    public function addReply( $data='' ){
        $re = M( THWK_R )
            ->add( $data );

        return $re;
    }

    public function editReply(  $data='',$where='' ){
        $re = M( THWK_R )
            ->where( $where )
            ->save( $data );

        return $re;
    }

    public function incForum($data,$where)
    {
        $info = M(THWK)
            ->where($where)
            ->setInc( $data['field'],$data['value']);

        return $info;
    }


    public function selectTags( $column='', $where='',  $limit='' ,$order='' ){
        $re = M( THWK_T )
            ->field( $column )
            ->where( $where )
            ->limit( $limit )
            ->order( $order )
            ->select();

        return $re;
    }

    public function findTags( $column='', $where='' ){
        $re = M( THWK_T )
            ->field( $column )
            ->where( $where )
            ->find();

        return $re;
    }

    public function addTags( $data='' ){
        $re = M( THWK_T )
            ->add( $data );

        return $re;
    }

    public function editTags(  $data='',$where='' ){
        $re = M( THWK_T )
            ->where( $where )
            ->save( $data );

        return $re;
    }

    public function delTags(  $where='' ){
        $re = M( THWK_T )
            ->where( $where )
            ->delete(  );

        return $re;
    }


    public function delSubject(  $data='',$where='' ){
        $re = M( THWK_S )
            ->where( $where )
            ->delete( );

        return $re;
    }


    public function selectSubject( $column='', $where='',  $limit='' ,$order='' ){
        $re = M( THWK_S )
            ->field( $column )
            ->where( $where )
            ->limit( $limit )
            ->order( $order )
            ->select();

        return $re;
    }

    public function addSubject( $data='' ){
        $re = M( THWK_S )
            ->add( $data );

        return $re;
    }

    public function editSubject(  $data='',$where='' ){
        $re = M( THWK_S )
            ->where( $where )
            ->save( $data );

        return $re;
    }

    public function findSubject(  $column='',$where='' ){
        $re = M( THWK_S )
            ->field($column)
            ->where( $where )
            ->find( );

        return $re;
    }

}