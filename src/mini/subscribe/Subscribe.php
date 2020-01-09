<?php
/**
 * Created by PhpStorm.
 * User: liyifei
 * Date: 2020/1/2
 * Time: 上午9:44
 */

namespace abei2017\wx\mini\subscribe;


use abei2017\wx\core\AccessToken;
use abei2017\wx\core\Driver;
use yii\httpclient\Client;

class Subscribe extends Driver
{
    const API_SEND_SSB = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=';

    private $accessToken = null;

    public function init()
    {
        parent::init();
        $this->accessToken = (new AccessToken(['conf' => $this->conf, 'httpClient' => $this->httpClient]))->getToken();
    }

    /**
     * 发送订阅消息
     *
     * @param $toUser
     * @param $templateId
     * @param $formId
     * @param $data
     * @param array $extra
     */
    public function send($toUser, $templateId, $page, $data)
    {
        $params = [
            'touser' => $toUser,
            'template_id' => $templateId,
            'page' => $page,
            'data' => $data,
            'access_token' => $this->accessToken
        ];

        $response = $this->post(self::API_SEND_SSB . $this->accessToken, $params)->setFormat(Client::FORMAT_JSON)->send();

        return $response->getContent();
    }
}