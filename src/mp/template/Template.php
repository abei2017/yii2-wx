<?php

namespace abei2017\wx\mp\template;

use abei2017\wx\core\Driver;
use abei2017\wx\core\AccessToken;
use abei2017\wx\core\Exception;
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
        $formatData = [];
        foreach($data as $key=>$val){
            if(is_string($val)){
                $formatData[$key] = ['value'=>$val,'color'=>'#4D4D4D'];
            }elseif (is_array($val)){
                if(isset($val['value'])){
                    $formatData[$key] = $val;
                }else{
                    $formatData[$key] = ['value'=>$val[0],'color'=>$val[1]];
                }
            }
        }

        $params = [
            'touser'=>$openId,
            'template_id'=>$templateId,
            'url'=>$url,
            'data'=>$formatData
        ];

        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_SEND_TEMPLATE_URL."?access_token=".$this->accessToken)
            ->setMethod('post')
            ->setData($params)
            ->setFormat(Client::FORMAT_JSON)->send();

        $data = $response->setFormat(Client::FORMAT_JSON)->getData();

        if(isset($data['errcode']) && $data['errcode'] != 0){
            throw new Exception($data['errmsg']);
        }

        return $data['msgid'];
    }

}