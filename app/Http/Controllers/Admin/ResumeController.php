<?php

namespace App\Http\Controllers\Admin;

use App\Models\Resume;

class ResumeController extends BaseController
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function index(){

        if ($this->isOnSubmit()) {
            $items = $this->request->post('items');
            if ($items) {
                foreach ($items as $id) {
                    Resume::where('id', $id)->delete();
                }
            }
            return ajaxReturn();
        }else {
            $items = Resume::where('status','1')->orderBy('id', 'DESC')->paginate(20);
            $this->assign([
                'itemlist'=>$items,
                'pagination'=>$items->links()
            ]);

            return $this->view('admin.resume.list');
        }
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function detail($id){
        $resume = Resume::where('id', $id)->first();
        $this->assign([
            'resume'=>$resume,
            'edus'=>$resume->edus,
            'works'=>$resume->works
        ]);

        return $this->view('admin.resume.detail');
    }
}
