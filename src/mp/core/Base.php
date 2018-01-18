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
use yii\base\Exception;

/**
 * Class Base
 * 这里呈现一些基础的内容
 * @package abei2017\wx\core
 */
class Base extends Driver {

    const BASE_IP_API = "https://api.weixin.qq.com/cgi-bin/getcallbackip";

    /**
     * 获取微信服务器IP或IP段
     */
    public function getValidIps(){
        $access = new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient,'extra'=>[]]);
        $accessToken = $access->getToken();

        $params = ['access_token'=>$accessToken];
        $response = $this->httpClient->createRequest()
            ->setUrl(self::BASE_IP_API)
            ->setMethod('get')
            ->setData($params)->send();

        $data = $response->getData();

        if(!isset($data["ip_list"])){
            throw new Exception($data['errmsg'],$data['errcode']);
        }

        return $data['ip_list'];
    }
}