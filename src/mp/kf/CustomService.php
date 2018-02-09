<?php

namespace abei2017\wx\mp\kf;

use yii\httpclient\Client;
use abei2017\wx\core\Driver;
use abei2017\wx\core\AccessToken;
use abei2017\wx\core\Exception;

class CustomService extends Driver {

    const API_SEND_URL = 'https://api.weixin.qq.com/cgi-bin/message/custom/send';

    private $accessToken;

    public function init(){
        parent::init();
        $this->accessToken = (new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken();
    }

    /**
     * 给某个用户发送某个类型的消息
     *
     * @param $openId
     * @param $type
     * @param $data
     */
    public function send($openId,$type,$data,$extra = []){
        $base = [
            'touser'=>$openId,
            'msgtype'=>$type,
        ];

        $params = array_merge($base,[$type=>$data],$extra);

        $this->httpClient->formatters = ['uncodeJson'=>'abei2017\wx\helpers\JsonFormatter'];
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_SEND_URL."?access_token={$this->accessToken}")
            ->setMethod('post')
            ->setFormat('uncodeJson')
            ->setData($params)
            ->send();

        if($response->isOk == false){
            throw new Exception('网络问题，没有得到服务器响应。');
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode']) && $data['errcode'] != 0){
            throw new Exception($data['errmsg']);
        }

        return true;
    }
}