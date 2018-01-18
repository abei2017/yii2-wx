<?php

namespace abei2017\wx\mp\js;

use abei2017\wx\core\Driver;
use yii\helpers\Json;
use yii\helpers\Url;
use Yii;
use yii\httpclient\Client;
use abei2017\wx\core\AccessToken;

class Js extends Driver {

    protected $cacheKey = 'wx-mp-js-ticket';

    const API_TICKET = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';

    public function init(){
        parent::init();
    }

    /**
     * 构造JSSDK配置参数
     * @param array $apis api接口地址
     * @param boolean $debug 是否启动调试模式
     * @return mixed
     */
    public function buildConfig($apis = [],$debug = false){
        $signPackage = $this->signature();

        $base = [
            'debug'=>$debug
        ];

        $config = array_merge($base,$signPackage,['jsApiList'=>$apis]);

        return Json::encode($config);
    }

    public function signature(){
        $url = Url::current([],true);
        $nonce = Yii::$app->security->generateRandomString(32);
        $timestamp = time();
        $ticket = $this->ticket();

        $sign = [
            'appId' => $this->conf['app_id'],
            'nonceStr' => $nonce,
            'timestamp' => $timestamp,
            'url' => $url,
            'signature' => $this->getSignature($ticket, $nonce, $timestamp, $url),
        ];

        return $sign;
    }

    public function getSignature($ticket,$nonce,$timestamp,$url){
        return sha1("jsapi_ticket={$ticket}&noncestr={$nonce}&timestamp={$timestamp}&url={$url}");
    }

    public function ticket(){
        $ticket = Yii::$app->cache->get($this->cacheKey);
        if($ticket == false){
            //  从服务器获取
            $accessToken = (new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken();
            $response = $this->httpClient->createRequest()
                ->setUrl(self::API_TICKET."?access_token={$accessToken}&type=jsapi")
                ->setMethod('get')->setFormat(Client::FORMAT_JSON)->send();

            $data = $response->setFormat(Client::FORMAT_JSON)->getData();

            $ticket = $data['ticket'];
            Yii::$app->cache->set($this->cacheKey,$ticket,$data['expires_in']-600);
        }

        return $ticket;
    }
}