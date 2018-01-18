<?php

namespace abei2017\wx\mp\menu;

use abei2017\wx\core\Driver;
use abei2017\wx\core\AccessToken;
use yii\httpclient\Client;

class Menu extends Driver {

    private $accessToken;

    const API_MENU_GET_URL = 'https://api.weixin.qq.com/cgi-bin/menu/get';
    const API_MENU_CREATE_URL = 'https://api.weixin.qq.com/cgi-bin/menu/create';

    public function init(){
        parent::init();
        $this->accessToken = (new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken();
    }

    /**
     * 获得当前菜单列表
     * @return mixed
     */
    public function ls(){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_MENU_GET_URL."?access_token=".$this->accessToken)
            ->setMethod('get')
            ->send();

        $response->setFormat(Client::FORMAT_JSON);
        return $response->getData();
    }

    /**
     * 生成菜单
     * @param $buttons array
     */
    public function create($buttons = []){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_MENU_CREATE_URL."?access_token=".$this->accessToken)
            ->setMethod('post')
            ->setFormat(Client::FORMAT_JSON)
            ->setData($buttons)
            ->send();

        $response->setFormat(Client::FORMAT_JSON);
        return $response->getData();
    }
}