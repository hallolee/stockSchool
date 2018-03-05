<?php
namespace Client\Controller;

class ProfileController extends GlobalController
{

    protected $m_m;

    public function _initialize( $check=true ){
        parent::_initialize( $check );
        $this->m_m = D('Client/Profile');
    }

    /*
    * 用户信息
    */
    public function showInfo(){
        $raw = $this->RxData;
        $ret = [];
        // 用户信息所需字段
        $keys = [ 'message_n','uid', 'xiaozhu', 'user', 'name', 'nickname', 'birthday', 'phone', 'icon', 'invite_code', 'uid', 'address', 'qq', 'city', 'level', 'department', 'teacher', 'invite_url', 'invite_n', 'invite_qr', 'roles' ];;

        // token 中的信息是否满足要求
        $new = 0;
        foreach ($keys as $val) {
            if( !isset( $this->out[ $val ] ) ){
                $new = 1;
            }else{
                $uinfo[ $val ] = $this->out[ $val ];
            }
        }

        // 不满足从数据库中获取，并更新 token 内容
        if( $new ){
            $uinfo = [];

            $column = '
                uid,xiaozhu, user,name,nickname,birthday,qq,city,
                roles,phone,icon,invite_code,teacher_uid,level_id,status_stu,status_te,forum_n,resource_n,hwk_n';
            $res = $this->m_m->findClient( $column, [ 'uid' => $this->out['uid'] ] );

            if( $res ){
                $refer_n = $this->m_m->findRefer('count(*) num',[ 'upline' => $this->out['uid'] ]);
                $uinfo['invite_n'] = $refer_n?$refer_n['num']:0;

                if( $res['level_id'] ){
                    $level = $this->m_m->findLevel( 'name', [ 'id' => $res['level_id'] ]);
                    $uinfo['level'] = $level['name'];
                }

                if( $res['teacher_uid'] ){
                    $teacher = $this->m_m->findClient( 'nickname', [ 'uid' => $res['teacher_uid'] ]);
                    $uinfo['teacher'] = $teacher['nickname'];
                }

                foreach ($res as $key => $value) {
                    if( $key == 'icon' )
                        $value = $value?C('PREURL').$value:$value;

                    $uinfo[ $key ] = ( $value || $value==='0' )?$value:'';
                }
                $uinfo['roles'] = $uinfo['roles']?explode(',', $uinfo['roles']):[];

                $user_role_info = D('Admin/System')->selectUserRoleGroup(
                    '',['a.uid'=>$this->out['uid'],'a.status'=>SYS_OK]);

                $uinfo['admin'] = 0;
                foreach($user_role_info as $v){
                    $role[] = $v['group_id'];
                    $uinfo['admin'] += pow(2,($v['group_id']-1));
                }

                $uinfo['role_id'] = serialize($role);
                $uinfo['message_n'] = $res['forum_n']+$res['hwk_n']+$res['resource_n'];

                //更新 token
                \Common\ValidDaTokenWrite( $uinfo, $raw['token'], TOKEN_APPEND );
            }
        }
        if( !empty( $uinfo ) )
            $ret = $uinfo;

        $this->retReturn( $ret );
    }


    /*
    * 修改用户信息
    */
    public function editInfo(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        $key = [ 'nickname','name','qq','city' ];
        foreach ($key as $val) {
            if( isset( $raw[ $val ] ) && $raw[ $val ] !== '' )
                $d[ $val ] = $raw[ $val ];
        }

        if( !$d ) goto END;

        // qq 只能填一次，再次则不修改
        if( isset( $d['qq'] ) && !empty( $this->out['qq'] ) ){
            unset( $d['qq'] );
        }

        $res = $this->m_m->saveClient( [ 'uid' => $this->out['uid'] ], $d );

        // 当用户未修改信息提交，也算修改成功
        if( $res !== false )
            $ret['status'] = E_OK;

        \Common\ValidDaTokenWrite( $d, $raw['token'], TOKEN_APPEND );

END:
        $this->retReturn( $ret );
    }

    /*
    * 修改用户头像
    */
    public function upload(){
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $head = C('PREURL');
        $uid = $this->out['uid'];

        $field = 'picture';
        $pre_path = C("UPLOAD_PATH");
        $realpath = $pre_path."profile/icon/u_$uid/";
        $conf = array(
            'pre' => 'icon',
            'types' => ['jpg', 'gif', 'png', 'jpeg'],
        );

        if( !is_dir($realpath) ) $z = mkdir( $realpath, 0775, true );

        // 上传图片
        $upload_res = \Common\_Upload($field,$realpath,$conf);
        if( $upload_res['status'] != 0 ){
            $ret =  $upload_res;
            goto END;
        }

        foreach ($upload_res['file'] as $key => $value) {
            //未压缩图片路径
            $path = $realpath.$value['savename'];

            //进行图片压缩
            $file_path = $value['savepath'].$value['savename'];
            $thumb = \Common\_Thumb($file_path,$value['savename']);
            if( $thumb['status'] != 0 ){
                $ret =  $thumb;
                goto END;
            }

            //压缩后的图片路径
            $thumbpath = $realpath.$thumb['savename'];

            break;
        }

        //修改用户头像（当前只保存压缩图）
        $data['icon'] = $thumbpath;
        $res = $this->m_m->saveClient( [ 'uid' => $uid ], $data );

        //未保存成功，清除上传图片
        if( $res === false ){
            unlink($path);
            unlink($thumbpath);
            goto END;
        }

        //更新 token 数据
        \Common\ValidDaTokenWrite( [ 'icon' => C('PREURL').$thumbpath ], $this->RxData['token'], TOKEN_APPEND );

        $ret[ 'status' ] = E_OK;
        $ret[ 'errstr' ] = '';
        $ret[ 'icon' ] = C('PREURL').$thumbpath;

END:
        $this->retReturn( $ret );
    }


    public function changePwd(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        $keys = [ 'old_pass', 'new_pass' ];
        foreach ($keys as $val) {
            if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                goto END;

            ${$val} = $raw[ $val ];
        }
        if( $new_pass == $old_pass ){
            $ret['status'] = E_NOCHANGE;
            goto END;
        }

        if( \Common\GetRealPass( $old_pass ) != $this->out['pass'] )
            goto END;

        $d['pass'] = \Common\GetRealPass( $new_pass );
        $res = $this->m_m->saveClient( [ 'uid' => $this->out['uid'] ], $d );

        if( $res !== false ){
            \Common\ValidDaTokenWrite( $d, $raw['token'], TOKEN_APPEND );
            $ret['status'] = E_OK;
        }
END:
        $this->retReturn( $ret );
    }

    public function editQuestionAnswer(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        $keys = [ 'old_question', 'old_answer', 'new_question', 'new_question_content', 'new_answer' ];
        foreach ($keys as $val) {
            if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                goto END;

            ${$val} = $raw[ $val ];
        }
        $where = [ 'uid' => $this->out['uid'] ];

        $chk_res = $this->m_m->findClient( 'question_id,answer', $where );
        if( $chk_res['question_id'] != $old_question || $chk_res['answer'] != md5( $old_answer ) ){
            $ret['status'] = E_USER;
            goto END;
        }

        $d = [
            'question_id' => $new_question,
            'question'    => $new_question_content,
            'answer'      => md5( $new_answer )
        ];
        $res = $this->m_m->saveClient( $where, $d );

        if( $res !== false )
            $ret['status'] = E_OK;
END:
        $this->retReturn( $ret );
    }


    //用户邀请的成员列表
    public function userInviteList()
    {
        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_n' => 0, 'page_start' => 0, 'data' => []];
        $head = C('PREURL');

        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page-1) . ',' . $num;
        $ret['page_start'] = $page;

        $where['a.upline'] = $raw['uid'];

        if( $raw['name'] )
            $where['b.name'] = [ 'like','%'.$raw['name'].'%' ];

        if( $raw['time_start'] && $raw['time_end'] )
            $where['a.atime'] = [
                [
                    'egt',
                    $raw['time_start']
                ],
                [
                    'lt',
                    $raw['time_end']
                ]
            ];

        $colunm = 'b.user,b.name,b.nickname,b.uid,b.icon,b.name,c.name as level,b.phone,a.atime';

        $count = $this->m_m->selectInviteUser('count(*) num', $where, '');
        $result = $this->m_m->selectInviteUser($colunm, $where, $limit, 'a.atime desc');

        if( $result ){
            foreach($result as &$v){
                $v['icon'] = $v['icon']?$head.$v['icon']:'';
            }
            $ret['page_n'] = count($result);
            $ret['data'] = $result;
        }

        if( $count )
            $ret['total'] = $count[0]['num'];
END:
        $this->retReturn($ret);
    }

    //用于修改手机号码的用户信息验证
    public function checkBeforeEditPhone(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        if( !isset( $raw['type'] ) ) goto END;
        switch ($raw['type']) {
            case 1:

                //验证参数( 必填 )
                $phone = $this->out['phone'];
                $keys = [ 'phonecode' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) ){
                        $ret['status'] = E_DATA;
                        goto END;
                    }

                    ${$val} = $raw[ $val ];
                }

                //验证短信验证码
                S( ['type'=>'memcached'] );
                $code = S( $phone );
                if( C('IS_TEST') && $phonecode == '1111' ){
                    $code = 'true';
                }
                else if( !$code ){
                    //验证码已过期或已失效
                    $ret[ 'status' ] = E_SMSCODE2;
                    goto END;
                }
                else if( $code != $phonecode ){
                    //验证码错误
                    $ret[ 'status' ] = E_SMSCODE;
                    goto END;
                }
                S( $phone, null );

                break;

            case 2:

                //验证参数( 必填 )
                $keys = [ 'question_id', 'answer' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) ){
                        $ret['status'] = E_DATA;
                        goto END;
                    }

                    ${$val} = $raw[ $val ];
                }

                $chk_res = $this->m_m->findClient( 'question_id,answer', [ 'uid' => $this->out['uid'] ] );
                if( $chk_res['question_id'] != $question_id || $chk_res['answer'] != md5( $answer ) ){
                    $ret['status'] = E_USER;
                    goto END;
                }

                break;

            default:
                $ret['status'] = E_DATA;
                goto END;
        }

        $ret['status'] = E_OK;
        S( 'Clientuid-'.$this->out['uid'], 1, C('EDIT_PHONE_CACHE_TIME') );
END:
        $this->retReturn( $ret );
    }

    // 修改手机号码
    public function editPhone(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $uid = $this->out['uid'];

        S( ['type'=>'memcached'] );
        if( !S( 'Clientuid-'.$this->out['uid'] ) )
            goto END;

        $keys = [  'phone', 'phonecode' ];
        foreach ($keys as $val) {
            if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) ){
                $ret['status'] = E_DATA;
                goto END;
            }

            ${$val} = $raw[ $val ];
        }

        if( $phone == $this->out['phone'] ){
            $ret['status'] = E_NOCHANGE;
            goto END;
        }

        //验证短信验证码
        $code = S( $phone );
        if( C('IS_TEST') && $phonecode == '1111' ){
            $code = 'true';
        }
        else if( !$code ){
            //验证码已过期或已失效
            $ret[ 'status' ] = E_SMSCODE2;
            goto END;
        }
        else if( $code != $phonecode ){
            //验证码错误
            $ret[ 'status' ] = E_SMSCODE;
            goto END;
        }
        S( $phone, null );

        $chk_res = $this->m_m->findClient( 'uid', [ 'uid' => [ 'neq', $uid ], 'phone' => $phone  ] );
        if( $chk_res ){
            $ret['status'] = E_EXIST;
            goto END;
        }

        $d['phone'] = $phone;
        $d['user'] = $phone;
        $res = $this->m_m->saveClient( [ 'uid' => $uid ], $d );

        if( $res !== false ){
            \Common\ValidDaTokenWrite( $d, $raw['token'], TOKEN_APPEND );
            $ret['status'] = E_OK;
            S( 'Clientuid-'.$this->out['uid'], null );
        }
END:
        $this->retReturn( $ret );
    }

}
