<?php

/*
 * This file is part of the abei2017/yii2-wx
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx\mp\js;

use abei2017\wx\core\Driver;
use yii\helpers\Json;
use yii\helpers\Url;
use Yii;
use yii\httpclient\Client;
use abei2017\wx\core\AccessToken;

/**
 * Js
 * 该助手类主要负责微信公众号JSSDK功能
 *
 * @author abei<abei@nai8.me>
 * @link https://nai8.me/yii2wx
 * @package abei2017\wx\mp\js
 */
class Js extends Driver {

    protected $cacheKey = 'wx-mp-js-ticket';

    const API_TICKET = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';

    public function init(){
        parent::init();
    }

    /**
     * 构造JSSDK配置参数
     *
     * @param array $apis api接口地址
     * @param boolean $debug 是否启动调试模式
     * @return mixed
     */
    public function buildConfig($apis = [],$debug = false){

        $signPackage = $this->signature();
        $config = array_merge(['debug'=>$debug],$signPackage,['jsApiList'=>$apis]);

        return Json::encode($config);
    }

    /**
     * 获得jssdk需要的配置参数
     * 这里包含appId、nonceStr、timestamp、url和signature。
     *
     * @return array
     */
    public function signature(){

        $url = Url::current([],true);
        $nonce = Yii::$app->security->generateRandomString(32);
        $timestamp = time();
        $ticket = $this->ticket();

        $sign = [
            'appId' => $this->conf['app_id'],
            'nonceStr' => $nonce,
            'timestamp' => $timestamp,
            'signature' => $this->getSignature($ticket, $nonce, $timestamp, $url),
        ];

        return $sign;
    }

    /**
     * 获得签名
     *
     * @param $ticket string jsapi_ticket
     * @param $nonce string 随机字符串
     * @param $timestamp integer 当前的时间戳
     * @param $url string 使用jssdk接口的url地址
     * @return string 签名
     */
    public function getSignature($ticket,$nonce,$timestamp,$url){

        return sha1("jsapi_ticket={$ticket}&noncestr={$nonce}&timestamp={$timestamp}&url={$url}");
    }

    /**
     * 获得jsapi_ticket
     * jsapi_ticket有访问次数的限制，同时每个jsapi_ticket有效期为7200秒，因此我们进行了存储。
     *
     * @return mixed
     */
    public function ticket(){

        $cacheKey = "{$this->cacheKey}-{$this->conf['app_id']}";
        $ticket = Yii::$app->cache->get($cacheKey);
        if($ticket == false){
            //  从服务器获取
            $accessToken = (new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken();
            $response = $this->get(self::API_TICKET."?access_token={$accessToken}&type=jsapi")->send();

            $data = $response->setFormat(Client::FORMAT_JSON)->getData();
            $ticket = $data['ticket'];
            Yii::$app->cache->set($cacheKey,$ticket,$data['expires_in']-600);
        }

        return $ticket;
    }
}