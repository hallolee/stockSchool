<?php
namespace Client\Model;

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

    public function selectClient( $column='', $where='', $order='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->select();

        return $re;
    }

    public function selectClientLimit( $column='', $where='', $limit='' , $order='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->limit( $limit )
            ->order( $order )
            ->select();

        return $re;
    }

    public function selectClientWithLevel( $column='', $where='', $order='' ) {
        $re = $this
            ->alias('a')
            ->field( $column )
            ->join('INNER JOIN '.TUSER_LEVEL.' b on b.id = a.level_id')
            ->where( $where )
            ->order( $order )
            ->select();

        return $re;
    }

    public function saveClient( $where='', $data='' ){
        $re = $this
            ->where( $where )
            ->save( $data );

        return $re;
    }


    public function saveClientMine(  $data='' ,$where=''){
        $re = $this
            ->where( $where )
            ->save( $data );

        return $re;
    }

    public function addClient( $d=[] ){
        list( $user, $inviteuser ) = $d;
        $t = time();
        $re = [];

        $m = M();
        $m->startTrans();

        $group = $m->table( TROLE )->field('id,name')->where( ['id' => 1] )->find();
        if( !$group )
            return $re;

        //添加用户
        $user['user'] = $user['user']?$user['user']:$user['phone'];
        $user['nickname'] = $user['nickname']?$user['nickname']:$user['name'];
        $user['atime'] = $t;
        $user['login_time'] = $t;
        $user['department_id'] = DEFAULT_DEP;
        $user['roles'] = $group['name'];
        $user['level_id'] = DEFAULT_LEVEL;
        $user['teacher_uid'] = 0;
        $user['invite_n'] = 0;
        $user['xiaozhu'] = \Common\random( 6, 'number' );
        $res1 = $m
            ->table(TUSER)
            ->add( $user );

        // 生成用户自己的邀请码
        $invitecode = 'c'.rand(10000, 99999).$res1;
        $res2 = $m
            ->table(TUSER)
            ->where([ 'uid' => $res1 ])
            ->save( [ 'invite_code' => $invitecode ] );

        // 确定邀请关系
        $refer = [
            'uid'   => $res1,
            'user'  => $user['user'],
            'name'  => $user['name'],
            'atime' => $t
        ];
        if( $inviteuser ){
            $refer['upline'] = $inviteuser['uid'];
            $refer['upline_user'] = $inviteuser['user'];
            $refer['upline_name'] = $inviteuser['name'];
            $this->incUser(['column'=>'invite_n','value'=>1],['uid'=>$inviteuser['uid']]);
        }
        $res3 = $m->table(TREFER)->add( $refer );

        $res4 = $m->table( TROLE_USER )->add([ 'uid' => $res1, 'group_id' => 1 ]);

        if( !$res1 || !$res2 || !$res3 || !$res4 ){
            $m->rollback();
        }else{
            $m->commit();
            $re['uid'] = $res1;
            $re['invite_code'] = $invitecode;
        }

        return $re;
    }

    public function findQQList($column = '',$where = ''){

        $res = M(TQQLIST)
            ->field($column)
            ->where($where)
            ->find();

        return $res;

    }

    public function incUser($data = '',$where){

        $res = M(TUSER)
            ->where($where)
            ->setInc($data['column'],$data['value']);

        return $res;

    }

    public function findDepartment($column = '',$where = ''){

        $res = M(TDEPART)
            ->field($column)
            ->where($where)
            ->find();

        return $res;

    }


    public function editQQList($data = '',$where = ''){

        $res = M(TQQLIST)
            ->where($where)
            ->save($data);

        return $res;

    }

    public function selectRole($column = '',$where = '',$limit = '',$order = ''){

        $res = M(TAUTH_GROUP)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;

    }


    public function findLevel($column = '', $where = '')
    {
        $re = M( TUSER_LEVEL )
            ->field($column)
            ->where($where)
            ->find();

        return $re;
    }


    public function selectLevel($column = '', $where = '')
    {
        $re = M( TUSER_LEVEL )
            ->field($column)
            ->where($where)
            ->select();

        return $re;
    }

    public function findTeacher($column = '', $where = '')
    {
        $re = M( TUSER_T )
            ->field($column)
            ->where($where)
            ->order('id desc')
            ->find();

        return $re;
    }

    public function findUser($column = '', $where = '')
    {
        $re = $this
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN '.TDEPART.' b on b.id = a.department_id')
            ->join('LEFT JOIN '.TUSER_T.' c on c.uid = a.teacher_uid')
            ->where($where)
            ->find();

        return $re;
    }

    public function selectInviteUser($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M(TREFER)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TUSER.' b on b.uid = a.uid')
            ->join('INNER JOIN '.TUSER_LEVEL.' c on c.id = b.level_id')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function findRefer( $column='', $where='' ) {
        $re = M( TREFER )
            ->field($column)
            ->where($where)
            ->find();

        return $re;
    }

    public function saveRefer( $where='', $data='' ) {
        $re = M( TREFER )
            ->where( $where )
            ->save( $data );

        return $re;
    }

    public function editUser($data = '',$where = ''){

        $res = M(TUSER)
            ->where($where)
            ->save($data);

        return $res;

    }

    public function selectQuestion($column = '',$where = '',$limit = '',$order = ''){

        $res = M(TUSER_Q)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $res;
    }

    public function findQuestion( $column='', $where='' ) {
        $re = M( TUSER_Q )
            ->field($column)
            ->where($where)
            ->find();

        return $re;
    }


    public function incTeacher($data = '',$where){

        $res = M(TUSER_T)
            ->where($where)
            ->setInc($data['column'],$data['value']);

        return $res;

    }


    public function decTeacher($data = '',$where){

        $res = M(TUSER_T)
            ->where($where)
            ->setDec($data['column'],$data['value']);

        return $res;

    }

    public function saveTeacher($data = '',$where){

        $res = M(TUSER_T)
            ->where($where)
            ->save($data);

        return $res;

    }

    public function delTeacher(d$where){

        $res = M(TUSER_T)
            ->where($where)
            ->delete();

        return $res;

    }

}
?>
