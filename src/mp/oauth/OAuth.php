<?php
/*
 * This file is part of the abei2017/yii2-wx
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx\mp\oauth;

use abei2017\wx\core\Driver;
use Yii;
use yii\httpclient\Client;
use abei2017\wx\core\Exception;

/**
 * web网页授权
 *
 * @package abei2017\wx\mp\oauth
 * @author abei<abei@nai8.me>
 * @link http://nai8.me/yii2wx
 */
class OAuth extends Driver {

    const API_AUTHORIZE_URL = "https://open.weixin.qq.com/connect/oauth2/authorize";
    const API_ACCESS_TOKEN_URL = "https://api.weixin.qq.com/sns/oauth2/access_token";
    const API_USER_INFO_URL = "https://api.weixin.qq.com/sns/userinfo";

    public $code = false;
    protected $accessToken = false;
    protected $openId = false;

    /**
     * 网页授权access_token
     * @var string
     */
    protected $accessTokenCacheKey = 'wx-oauth-access-token';

    /**
     * refresh_token
     * @var string
     */
    protected $refreshAccessTokenCacheKey = 'wx-oauth-refresh-access-token';

    /**
     * 跳转到授权页面
     */
    public function send(){
        $url = self::API_AUTHORIZE_URL."?appid={$this->conf['app_id']}&redirect_uri={$this->conf['oauth']['callback']}&response_type=code&scope={$this->conf['oauth']['scopes']}&state=STATE#wechat_redirect";
        header("location:{$url}");
    }

    /**
     * 获得web授权的access token
     * 该方法需要从get参数中获取code来换取。
     * @return bool
     * @throws Exception
     */
    protected function initAccessToken(){
        if($this->accessToken){
            return $this->accessToken;
        }

        $code = $this->getCode();
        $url = self::API_ACCESS_TOKEN_URL."?appid={$this->conf['app_id']}&secret={$this->conf['secret']}&code={$code}&grant_type=authorization_code";

        $response = $this->get($url)->send();
        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();
        if(isset($data['errcode']) && $data['errcode'] != 0){
            throw new Exception($data['errmsg'], $data['errcode']);
        }

        $data = $response->getData();

        $this->accessToken = $data['access_token'];
        $this->openId = $data['openid'];
    }

    /**
     * 获得web授权的access token和openId
     * @return bool
     */
    public function getOpenId(){
        if($this->openId){
            return $this->openId;
        }

        $this->initAccessToken();

        return $this->openId;
    }

    protected function getCode(){
        if($this->code == false){
            $this->code = Yii::$app->request->get('code');
        }

        return $this->code;
    }

    /**
     * 通过web授权的access_token获得用户信息
     *
     * @return mixed
     * @throws Exception
     */
    public function user(){
        $this->initAccessToken();
        $url = self::API_USER_INFO_URL."?access_token={$this->accessToken}&openid={$this->openId}&lang=zh_CN";

        $response = $this->get($url)->send();
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

}