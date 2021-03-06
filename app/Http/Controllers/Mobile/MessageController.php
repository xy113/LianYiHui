<?php
/**
 * Created by PhpStorm.
 * User: 12394
 * Date: 2018-4-17
 * Time: 11:13
 */
namespace App\Http\Controllers\Mobile;

use App\Models\Member;
use App\Models\MemberEducation;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends BaseController
{
    public function index(){
        $id = $this->request->get('id');
        $isMe = false;
        if (empty($id)){
            $id = $this->uid;
            $isMe = true;
        }
        $user = Member::find($id);
        $message = Message::where(['uid'=>$id,'level'=>'1'])->orderBy('created_at','desc')->get();
        $edus = MemberEducation::where('uid',$id)->where('status','>','0')->get();
        $this->assign(['message'=>$message,'user'=>$user,'isMe'=>$isMe,'uid'=>$this->uid,'verify_status'=>trans('member.verify_status'),'edus'=>$edus]);
        return $this->view('mobile.message.index');
    }
    public function leaveMessage(){
        $dt = new \DateTime();
        $req = $this->request->post();
//        dd($req);
        $msg = new Message;
        $msg->uid = intval($req['uid']);
        $msg->vid = $this->uid;
        $msg->reply_id = $req['reply_id'];
        $msg->content = $req['content'];
        $msg->created_at = $dt->format('y-m-d H:i:s');
        if($req['reply_id']==0){
            $msg->parent_id = 0;
        }else{
            $msg->parent_id = Message::find($req['reply_id'])['level']=='1'?$req['reply_id']:Message::find($req['reply_id'])['parent_id'];
            $msg->level = '0';
        }
        $msg->save();
        return ajaxReturn();
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function delMessage(){
        $req = $this->request->post();
        $msg = Message::find($req['id']);
        $msgs = Message::where('parent_id',$req['id'])->delete();
        $msg->delete();
        return ajaxReturn();
    }
}
