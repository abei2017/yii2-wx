<?php

namespace abei2017\wx\mp\qrcode;

use abei2017\wx\core\Driver;
use abei2017\wx\AccessToken;
use yii\httpclient\Client;

/**
 * 长链接转短地址助手
 * @package abei2017\wx\mp\qrcode
 */
class Shorturl extends Driver {

    private $accessToken;

    const API_SHORT_URL = 'https://api.weixin.qq.com/cgi-bin/shorturl';

    public function init(){
        parent::init();
        $this->accessToken = (new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken();
    }

    /**
     * @param string $longUrl
     */
    public function toShort($longUrl = ''){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_SHORT_URL."?access_token=".$this->accessToken)
            ->setMethod('post')
            ->setFormat(Client::FORMAT_JSON)
            ->setData(['action'=>'long2short','long_url'=>$longUrl])->send();

        $data = $response->getData();

        return $data['short_url'];
    }

}