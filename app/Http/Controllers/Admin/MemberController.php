<?php

namespace App\Http\Controllers\Admin;

use App\Models\Member;
use App\Models\MemberGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class MemberController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request){
        $condition = $queryParams = $data = array();

        $uid = htmlspecialchars($request->input('uid'));
        if ($uid) {
            $condition[] = ['m.uid', '=', $uid];
            $queryParams['uid'] = $uid;
        }
        $data['uid'] = $uid;

        $username = htmlspecialchars($request->input('username'));
        if ($username) {
            $condition[] = ['m.username', 'LIKE', $username];
            $queryParams['username'] = $username;
        }
        $data['username'] = $username;

        $mobile = htmlspecialchars($request->input('mobile'));
        if ($mobile) {
            $condition[] = ['m.mobile', '=', $mobile];
            $queryParams['mobile'] = $mobile;
        }
        $data['mobile'] = $mobile;

        $email = htmlspecialchars($request->input('email'));
        if ($email) {
            $condition[] = ['m.email', '=', $email];
            $queryParams['email'] = $email;
        }
        $data['email'] = $email;

        $reg_time_begin = htmlspecialchars($request->input('reg_time_begin'));
        if ($reg_time_begin) {
            $condition[] = ['s.regdate', '>', strtotime($reg_time_begin)];
            $queryParams['reg_time_begin'] = $reg_time_begin;
        }
        $data['reg_time_begin'] = $reg_time_begin;

        $reg_time_end = htmlspecialchars($request->input('reg_time_end'));
        if ($reg_time_end) {
            $condition[] = ['s.regdate', '<', strtotime($reg_time_end)];
            $queryParams['reg_time_end'] = $reg_time_end;
        }
        $data['reg_time_end'] = $reg_time_end;

        $last_visit_begin = htmlspecialchars($request->input('last_visit_begin'));
        if ($last_visit_begin) {
            $condition[] = ['s.lastvisit', '>', strtotime($last_visit_begin)];
            $queryParams['last_visit_begin'] = $last_visit_begin;
        }
        $data['last_visit_begin'] = $last_visit_begin;

        $last_visit_end = htmlspecialchars($request->input('last_visit_end'));
        if ($last_visit_end) {
            $condition[] = ['s.lastvisit', '<', strtotime($last_visit_end)];;
            $queryParams['last_visit_end'] = $last_visit_end;
        }
        $data['last_visit_end'] = $last_visit_end;

        $memberlist = DB::table('member as m')
                        ->leftJoin('member_status as s', 'm.uid', '=', 's.uid')
                        ->where($condition)
                        ->select('m.*','s.regdate','s.lastvisit','s.regip','s.lastvisitip')
                        ->orderBy('uid', 'ASC')
                        ->paginate(20);
        $data['pagination'] = $memberlist ? $memberlist->links() : '';

        $grouplist = [];
        foreach (MemberGroup::all() as $group){
            $grouplist[$group->gid] = $group;
        }

        $data['memberlist'] = [];
        foreach ($memberlist as $member){
            $member->grouptitle = $grouplist[$member->gid]->title;
            $data['memberlist'][$member->uid] = $member;
        }
        $data['member_status'] = trans('member.member_status');
        return view('admin.member.list',$data);
    }

    public function delete(Request $request){
        $members = $request->input('members');
        foreach ($members as $uid){
            if ($uid != 1000000){
                Member::deleteAll($uid);
            }
        }
        return $this->showAjaxReturn();
    }

    /**
     * 添加用户
     */
    public function add(){
        global $_G,$_lang;
        if ($this->checkFormSubmit()) {
            $errno = 0;
            $membernew = $_GET['membernew'];
            cookie('membernew',serialize($membernew),600);
            if ($membernew['username'] && $membernew['password']) {
                $returns = member_register($membernew);
                if ($returns['errno']) {
                    $this->showError($returns['error']);
                }else {
                    $this->showSuccess('member_add_succeed');
                }
            }else {
                $this->showError('invalid_parameter');
            }
        }else {

            $_Grouplist = usergroup_get_list(0);
            $member = unserialize(cookie('membernew'));

            $_G['title'] = 'memberlist';
            include template('member/member_form');
        }
    }

    /**
     * 编辑用户
     */
    public function edit(){
        $uid = intval($_GET['uid']);
        if ($this->checkFormSubmit()) {

            $membernew = $_GET['membernew'];
            if (member_get_num(array('username'=>$membernew['username'])) > 1){
                $this->showError('username_be_occupied');
            }

            if ($membernew['email']) {
                if (member_get_num(array('email'=>$membernew['email'])) > 1){
                    $this->showError('email_be_occupied');
                }
            }

            if ($membernew['mobile']) {
                if (member_get_num(array('mobile'=>$membernew['mobile'])) > 1){
                    $this->showError('mobile_be_occupied');
                }
            }

            if ($membernew['password']) {
                $membernew['password'] = getPassword($membernew['password']);
            }else {
                unset($membernew['password']);
            }

            member_update_data(array('uid'=>$uid), $membernew);
            $this->showSuccess('update_succeed');
        }else {
            global $_G,$_lang;
            $member = member_get_data(array('uid'=>$uid));
            $_Grouplist  = usergroup_get_list(0);

            $_G['title'] = 'memberlist';
            include template('member/member_form');
        }
    }

    /**
     * 移动到分组
     */
    public function moveto(){
        $uids = trim($_GET['uids']);
        $target = intval($_GET['target']);
        member_update_data(array('uid'=>array('IN', $uids)), array('gid'=>$target));
        $this->showSuccess('update_succeed', U('a=member_list&gid='.$target));
    }

    public function grouplist(){
        global $_G,$_lang;
        G('menu', 'membergroup');

        if($this->checkFormSubmit()){
            $delete = $_GET['delete'];
            if ($delete && is_array($delete)) {
                $deleteids = implodeids($delete);
                usergroup_delete_data(array('gid'=>array('IN', $deleteids), 'type'=>'custom'));
                $_Group = M('member_group')->where(array('type'=>'custom'))->order('creditslower ASC')->getOne();
                member_update_data(array('gid'=>array('IN', $deleteids)), array('gid'=>$_Group['gid']));
            }

            $_Grouplist = $_GET['grouplist'];
            if ($_Grouplist && is_array($_Grouplist)) {
                foreach ($_Grouplist as $_Gid=>$_Group){
                    if ($_Group['title']) {
                        $_Group['perm'] = serialize($_Group['perm']);
                        if ($_Gid > 0){
                            usergroup_update_data(array('gid'=>$_Gid), $_Group);
                        }else {
                            usergroup_add_data($_Group);
                        }
                    }
                }
            }

            $this->showSuccess('update_succeed');
        }else{
            $grouplist = array();
            foreach (member_get_group_list() as $_Group){
                $usergrouplist[$_Group['type']][$_Group['gid']] = $_Group;
            }
            include template('member/member_group');
        }
    }
}