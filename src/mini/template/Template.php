<?php

namespace abei2017\wx\mini\template;

use abei2017\wx\core\Driver;
use Yii;
use yii\httpclient\Client;
/**
 * Class Tmpl.
 *
 * @package abei2017\mini\tmpl
 */
class Template extends Driver {

    const API_SEND_TMPL = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=';
    /**
     * 发送模板消息
     *
     * @param $toUser
     * @param $templateId
     * @param $formId
     * @param $data
     * @param array $extra
     */
    public function send($toUser,$templateId,$formId,$data,$extra = []){
        $params = array_merge([
            'touser'=>$toUser,
            'template_id'=>$templateId,
            'form_id'=>$formId,
            'data'=>$data,
        ],$extra);
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_SEND_TMPL.$this->accessToken->getToken())
            ->setMethod('post')
            ->setFormat(Client::FORMAT_JSON)
            ->setData($params)->send();
        return $response->getContent();
    }
}