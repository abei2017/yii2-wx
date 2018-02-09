<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/16
 * Time: 17:39
 */
namespace abei2017\wx\mini\qrcode;

use abei2017\wx\core\Driver;
use Yii;
use yii\httpclient\Client;
use abei2017\wx\core\AccessToken;

class Qrcode extends Driver {

    //  获取不受限制的小程序码
    const API_UN_LIMIT_CREATE = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit';

    //  生成永久小程序码（数量有限）
    const API_CREATE = 'https://api.weixin.qq.com/wxa/getwxacode';

    private $accessToken = null;

    public function init(){

        parent::init();
        $this->accessToken = (new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken();
    }

    /**
     * 生成一个不限制的二维码
     * @param $scene
     * @param $page
     * @param array $extra
     * @return \yii\httpclient\Request;
     */
    public function unLimit($scene,$page,$extra = []){
        $params = array_merge(['scene'=>$scene,'page'=>$page],$extra);
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_UN_LIMIT_CREATE."?access_token=".$this->accessToken)
            ->setMethod('post')
            ->setFormat(Client::FORMAT_JSON)
            ->setData($params)->send();

        return $response->getContent();
    }
}