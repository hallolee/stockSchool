<?php
namespace Admin\Model;

class ProfileModel extends GlobalModel
{
    protected $tableName = TUSER; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

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
            ->find();
        return $re;
    }

    public function selectRoles( $column='', $where='' ) {
        $re = M(TAUTH_GROUP)
            ->field( $column )
            ->where( $where )
            ->select();
        return $re;
    }




    public function findUserWith($column = '', $where = '')
    {
        $re = $this
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN '.TDEPART.' b on b.id = a.department_id')
            ->join('LEFT JOIN '.TUSER_LEVEL.' c on c.id = a.level_id')
            ->join('LEFT JOIN '.TUSER_T.' d on d.uid = a.teacher_uid')
            ->where($where)
            ->find();

        return $re;
    }


    public function findUpline($column = '', $where = '')
    {
        $re = M(TREFER)
            ->alias('b')
            ->field($column)
            ->join('LEFT JOIN '.TUSER.' a on a.uid = b.upline')
            ->where($where)
            ->find();

        return $re;
    }





    public function selectClient( $column='', $where='', $limit,$order='' ) {
        $re = $this
            ->alias('a')
            ->field( $column )
            ->where( $where )
            ->limit( $limit )
            ->order( $order )
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
            ->where($where )
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

    public function saveClientExt( $where='', $data='' ){
        $re = M( TCLIENT_EXT )
            ->where( $where )
            ->save( $data );

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


}
?>