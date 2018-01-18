<?php
/*
 * This file is part of the abei2017/yii2-wx
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace abei2017\wx\core;

use Yii;
use yii\base\Exception;

/**
 * Class AccessToken
 * 获取微信AccessToken接口类
 * @link http://nai8.me
 * @package abei2017\wx\accessToken
 * @author abei<abei@nai8.me>
 */
class AccessToken extends Driver {

    //  获取access_token的接口地址
    const API_TOKEN_GET = 'https://api.weixin.qq.com/cgi-bin/token';

    //  存放access_token的缓存
    protected $cacheKey = 'wx-access-token';

    /**
     * 获得access_token
     *
     * @param $cacheRefresh boolean 是否刷新缓存
     * @author abei<abei@nai8.me>
     * @return string
     */
    public function getToken($cacheRefresh = false){
        if($cacheRefresh == true){
            Yii::$app->cache->delete($this->cacheKey);
        }

        $data = Yii::$app->cache->get($this->cacheKey);
        if($data == false){
            $token = $this->getTokenFromServer();

            $data = $token['access_token'];
            Yii::$app->cache->set($this->cacheKey,$data,$token['expires_in']-600);
        }

        return $data;
    }

    /**
     * 从服务器上获得accessToken。
     *
     * @return mixed
     * @author abei<abei@nai8.me>
     * @throws Exception
     */
    public function getTokenFromServer(){
        $params = [
            'grant_type'=>'client_credential',
            'appid'=>$this->conf['app_id'],
            'secret'=>$this->conf['secret'],
        ];

        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_TOKEN_GET)
            ->setMethod('get')
            ->setData($params)->send();

        $data = $response->getData();
        if(!isset($data['access_token'])){
            throw new Exception($data['errmsg'],$data['errcode']);
        }

        return $data;
    }
}