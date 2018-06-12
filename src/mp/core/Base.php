<?php

/*
 * This file is part of the abei2017/yii2-wx
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx\mp\core;

use abei2017\wx\core\AccessToken;
use abei2017\wx\core\Exception;
use abei2017\wx\core\Driver;

/**
 * Base
 * 这里呈现一些基础的内容
 *
 * @package abei2017\wx\core
 * @author abei<abei@nai8.me>
 * @link https://nai8.me/yii2wx
 */
class Base extends Driver {

    const API_BASE_IP_URL = "https://api.weixin.qq.com/cgi-bin/getcallbackip";

    /**
     * 获取微信服务器IP或IP段
     */
    public function getValidIps(){
        $access = new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]);
        $accessToken = $access->getToken();

        $response = $this->get(self::API_BASE_IP_URL,['access_token'=>$accessToken])->send();

        $data = $response->getData();
        if(isset($data["ip_list"]) == false){
            throw new Exception($data['errmsg'],$data['errcode']);
        }

        return $data['ip_list'];
    }
}