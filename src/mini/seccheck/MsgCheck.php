<?php
/**
 * Created by PhpStorm.
 * User: leeyifiei
 * Date: 2019/9/27
 * Time: 9:55 AM
 */

namespace abei2017\wx\mini\seccheck;


use abei2017\wx\core\AccessToken;
use abei2017\wx\core\Driver;
use yii\httpclient\Client;
use yii\web\BadRequestHttpException;

class MsgCheck extends Driver
{
    const API_SEND_CHECK = 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token=';

    private $accessToken = null;

    public function init()
    {
        parent::init();
        $this->accessToken = (new AccessToken(['conf' => $this->conf, 'httpClient' => $this->httpClient]))->getToken();
    }


    public function check($content)
    {
        $response = $this->httpClient
            ->createRequest()
            ->setUrl(static::API_SEND_CHECK . $this->accessToken)
            ->setFormat(Client::FORMAT_CURL)
            ->setMethod('POST')
            ->setContent(json_encode(['content' => $content], JSON_UNESCAPED_UNICODE))
            ->send();

        if ($response->isOk) {
            $content = json_decode($response->getContent(), 1);

            if ($content['errcode'] == 0) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new BadRequestHttpException($response->content);
        }
    }
}