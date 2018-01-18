<?php
namespace abei2017\wx\mini\user;

use abei2017\wx\core\Driver;
use abei2017\wx\core\AccessToken;
use yii\httpclient\Client;

class User extends Driver {
    const API_CODE_TO_SESSION_URL = "https://api.weixin.qq.com/sns/jscode2session";

    public function codeToSession($code){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_CODE_TO_SESSION_URL."?appid={$this->conf['app_id']}&secret={$this->conf['secret']}&js_code={$code}&grant_type=authorization_code")
            ->setMethod('get')
            ->setFormat(Client::FORMAT_JSON)
            ->send();

        if($response->isOk){
            $data = $response->getData();
            return $data;
        }
    }
}