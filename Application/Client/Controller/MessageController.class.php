<?php
namespace Client\Controller;

class MessageController extends GlobalController
{
    protected $model;
    protected $modelProfile;

    public function _initialize($check = true)
    {
        parent::_initialize($check);
        $this->model = D('Client/Message');
        $this->modelProfile = D('Client/Profile');
    }

    /**
     * 通知列表
     */

    public function noticeList()
    {
        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'resource_n' => 0, 'forum_n' => 0, 'hwk_n' => 0, 'data' => []];

        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page - 1) . ',' . $num;
        $ret['page_start'] = $page;
        if ($raw['type']) {
            $where['type'] = $raw['type'];
            switch($raw['type']){
                case M_FORUM:
                    $column = 'forum_n';
                    break;
                case M_RESOURCE:
                    $column = 'resource_n';
                    break;
                case M_HWK:
                    $column = 'hwk_n';
                    break;
                default:
                    goto END;
            }
        } else {
            goto END;
        }
        $ret['hwk_n'] = $this->out['hwk_n'];
        $ret['forum_n'] = $this->out['forum_n'];
        $ret['resource_n'] = $this->out['resource_n'];

        $where['uid_to'] = $this->out['uid'];
        $result = $this->model->selectMessage(
           '',
            $where,
            $limit,
            'atime desc'
        );

        if(!$result)
            goto END;
        $uid = [];
        foreach ($result as $v) {
            $uid[] = $v['uid_from'];
        }
        $user = \Common\getUserInfo($uid);

        foreach ($result as $k => &$v) {
            $v['icon'] = $user[$v['uid_from']]['icon'];
            $v['name'] = $user[$v['uid_from']]['name'];
            $v['nickname'] = $user[$v['uid_from']]['nickname'];
        }
        unset($v);

        $ret['page_n'] = count($ret['data']);
        $ret['data'] = $result ? $result : [];

        $count = $this->model->selectMessage('count(*) total', $where);
        if ($count)
            $ret['total'] = $count[0]['total'];

        //统计数归零
        $clear_res = $this->modelProfile->saveClient(['uid'=>$this->out['uid']],[$column=>0]);

        $this->out['message_n'] -= $this->out[$column];
        $this->out[$column] = 0;

        //更新 token
        \Common\ValidDaTokenWrite( $this->out, $raw['token'], TOKEN_APPEND );

END:
        $this->retReturn($ret);
    }


}