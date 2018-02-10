<?php

/*
 * This file is part of the abei2017/yii2-wx
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx\mp\menu;

use abei2017\wx\core\Driver;
use abei2017\wx\core\AccessToken;
use yii\httpclient\Client;
use abei2017\wx\core\Exception;

/**
 * Menu
 * 微信公众号菜单助手
 *
 * @author abei<abei@nai8.me>
 * @link https://nai8.me/yii2wx
 * @package abei2017\wx\mp\menu
 */
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
     *
     * @throws Exception
     * @return mixed
     */
    public function ls(){
        $response = $this->get(self::API_MENU_GET_URL."?access_token=".$this->accessToken)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();
        if(isset($data['errcode']) && $data['errcode'] != 0){
            throw new Exception($data['errmsg'], $data['errcode']);
        }

        return $data;
    }

    /**
     * 生成菜单
     *
     * @throws Exception
     * @param $buttons array 菜单数据
     * @return boolean
     */
    public function create($buttons = []){
        $response = $this->post(self::API_MENU_CREATE_URL."?access_token=".$this->accessToken,$buttons)
            ->setFormat(Client::FORMAT_JSON)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();
        if(isset($data['errcode']) && $data['errcode'] != 0){
            throw new Exception($data['errmsg'], $data['errcode']);
        }

        return true;
    }
}