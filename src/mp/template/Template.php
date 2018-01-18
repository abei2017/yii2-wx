<?php

namespace abei2017\wx\mp\template;

use abei2017\wx\core\Driver;
use abei2017\wx\core\AccessToken;
use yii\httpclient\Client;

/**
 * 模板消息助手
 * @package abei2017\wx\mp\template
 */
class Template extends Driver {

    private $accessToken;

    const API_SEND_TEMPLATE_URL = 'https://api.weixin.qq.com/cgi-bin/message/template/send';

    public function init(){
        parent::init();
        $this->accessToken = (new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken();
    }

    /**
     * 发送一个模板消息
     */
    public function send($openId,$templateId,$url,$data){
        $params = [
            'touser'=>$openId,
            'template_id'=>$templateId,
            'url'=>$url,
            'data'=>$data
        ];

        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_SEND_TEMPLATE_URL."?access_token=".$this->accessToken)
            ->setMethod('post')
            ->setData($params)
            ->setFormat(Client::FORMAT_JSON)->send();

        $data = $response->setFormat(Client::FORMAT_JSON)->getData();

        return $data;
    }

}