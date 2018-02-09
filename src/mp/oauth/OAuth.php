<?php

namespace abei2017\wx\mp\oauth;

use abei2017\wx\core\Driver;
use Yii;

/**
 * web网页授权
 * @package abei2017\wx\mp\oauth
 */
class OAuth extends Driver {

    const API_URL = "https://open.weixin.qq.com/connect/oauth2/authorize";
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

    public function send(){
        $url = self::API_URL."?appid={$this->conf['app_id']}&redirect_uri={$this->conf['oauth']['callback']}&response_type=code&scope={$this->conf['oauth']['scopes']}&state=STATE#wechat_redirect";
        header("location:{$url}");
    }

    protected function initAccessToken(){
        if($this->accessToken){
            return $this->accessToken;
        }

        $code = $this->getCode();
        $url = self::API_ACCESS_TOKEN_URL."?appid={$this->conf['app_id']}&secret={$this->conf['secret']}&code={$code}&grant_type=authorization_code";

        $response = $this->httpClient->createRequest()
            ->setMethod('get')
            ->setUrl($url)->send();

        $accessTokenInfo = $response->getData();

        $this->accessToken = $accessTokenInfo['access_token'];
        $this->openId = $accessTokenInfo['openid'];
    }

    protected function getCode(){
        if($this->code == false){
            $this->code = Yii::$app->request->get('code');
        }

        return $this->code;
    }

    public function user(){
        $this->initAccessToken();
        $url = self::API_USER_INFO_URL."?access_token={$this->accessToken}&openid={$this->openId}&lang=zh_CN";

        $response = $this->httpClient->createRequest()
            ->setMethod('get')
            ->setUrl($url)->send();

        return $response->getData();
    }

}