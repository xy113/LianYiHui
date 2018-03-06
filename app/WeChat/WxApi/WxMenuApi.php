<?php
/**
 * Created by PhpStorm.
 * User: songdewei
 * Date: 2017/10/10
 * Time: 下午3:20
 */

namespace App\WeChat\WxApi;

use App\Helpers\Http;

class WxMenuApi extends WxApi
{
    /**
     * 创建自定义菜单
     * @param $menus
     * @return mixed
     * @throws \Exception
     */
    public function create($menus){
        $res = Http::curlPost("https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$this->getAccessToken(), $menus);
        return $res;
    }

    /**
     * 删除自定义菜单
     * @return mixed
     * @throws \Exception
     */
    public function delete(){
        $res = Http::curlGet("https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=".$this->getAccessToken());
        return $res;
    }
}
