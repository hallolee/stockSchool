<?php
namespace Admin\Model;

class AdminModel extends GlobalModel
{
    protected $tableName = TCLIENT; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    public function _initialize() {
        parent::_initialize();
    }


    public function findClient( $column='', $where='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->find();

        return $re;
    }

    public function selectClient( $column='', $where='', $order='' ) {
        $re = $this
            ->field(  )
            ->where( $where )
            ->order( $order )
            ->select();

        return $re;
    }

    public function getClient( $column='', $where='', $order='', $limit='' ){
        $re = $this
            ->alias('a')
            ->field( $column )
            ->join( 'INNER JOIN '.TCLIENT_EXT.' b on a.uid = b.uid ' )
            ->where( $where )
            ->order( $order )
            ->limit( $limit )
            ->select();

        return $re;
    }


    public function addClient( $data='' ){
        $re = $this
            ->add( $data );

        return $re;
    }


    public function saveClient( $where='', $data='' ){
        $re = $this
            ->where( $where )
            ->save( $data );

        return $re;
    }

    public function registerClient( $d ) {
        $re['uid'] = 0;
        $t = time();

        $m = M();
        $m->startTrans();

        $user_d = [
            'openid'      => $d['openid'],
            'atime'     => $t
        ];

        $result = $m->table(TCLIENT)->add( $user_d );

        $other_d = [
            'uid'   => $result,
        ];

        $client_ext = $m->table(TCLIENT_EXT)->add($other_d);

        if( !$result || !$client_ext ){
            $m->rollback();
            goto END;
        }

        $m->commit();

        $user_d['uid'] = $result;
        $user_d['openid'] = $d['openid'];
        $re = $user_d;
END:
        return $re;
    }

    public function selectClientExt( $column='', $where='', $order='' ) {
        $re = M( TCLIENT_EXT )
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->select();

        return $re;
    }

    public function saveClientExt( $where='', $data='' ){
        $re = M( TCLIENT_EXT )
            ->where( $where )
            ->save( $data );

        return $re;
    }

    public function addClientExt( $data='' ){
        $re = M( TCLIENT_EXT )
            ->add( $data );

        return $re;
    }


    public function setIncClientExt( $w='', $column='' ){
        $re = M( TCLIENT_EXT )
            ->where( $w )
            ->setInc($column);

        return $re;
    }

    public function setDecClientExt( $w='', $column='' ){
        $re = M( TCLIENT_EXT )
            ->where( $w )
            ->setDec($column);

        return $re;
    }


    public function getExpOrder( $uid='0' ){
        $re = M( TEXP_O )
            ->alias( 'a' )
            ->column( 'a.id,c.name task_name,a.exp num,a.type,a.atime' )
            ->join( 'LEFT JOIN '.TTASK_ORDER.' b on a.src_id = b.id ' )
            ->join( 'LEFT JOIN '.TTASK.' c on b.task_id = c.id '  )
            ->where( [ 'a.uid' => $uid ] )
            ->order( 'a.atime DESC' )
            ->select();

        return $re;
    }


    public function getGoldOrder( $uid='0' ){
        $re = M( TGOLD_O )
            ->alias( 'a' )
            ->column( 'a.id,c.name task_name,a.gold num,a.type,a.atime' )
            ->join( 'LEFT JOIN '.TTASK_ORDER.' b on a.src_id = b.id ' )
            ->join( 'LEFT JOIN '.TTASK.' c on b.task_id = c.id '  )
            ->where( [ 'a.uid' => $uid ] )
            ->order( 'a.atime DESC' )
            ->select();

        return $re;
    }


    public function getFollow( $uid='0' ){
        $re = M( TFOLLOW )
            ->alias( 'a' )
            ->column( 'a.fid uid,b.nickname,c.honor,b.sex,b.city,a.atime' )
            ->join( 'LEFT JOIN '.TCLIENT.' b on a.fid = b.uid ' )
            ->join( 'LEFT JOIN '.TCLIENT_EXT.' c on a.fid = c.uid ' )
            ->where( [ 'a.uid' => $uid ] )
            ->order( 'a.atime DESC' )
            ->select();

        return $re;
    }


    public function getFollower( $uid='0' ){
        $re = M( TFOLLOW )
            ->alias( 'a' )
            ->column( 'a.uid uid,b.nickname,c.honor,b.sex,b.city,a.atime' )
            ->join( 'LEFT JOIN '.TCLIENT.' b on a.uid = b.uid ' )
            ->join( 'LEFT JOIN '.TCLIENT_EXT.' c on a.uid = c.uid ' )
            ->where( [ 'a.fid' => $uid ] )
            ->order( 'a.atime DESC' )
            ->select();

        return $re;
    }

}
?>