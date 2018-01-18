<?php

namespace abei2017\wx\mp\user;

use abei2017\wx\core\Driver;
use abei2017\wx\core\AccessToken;
use abei2017\wx\core\Exception;
use yii\httpclient\Client;
use yii\helpers\Json;

class User extends Driver {

    //  获得用户信息的接口地址
    const API_USER_INFO_URL = 'https://api.weixin.qq.com/cgi-bin/user/info';
    //  批量获取用户信息
    const API_BATCH_USER_INFO_URL = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget';
    //  用户列表
    const API_USER_LIST_URL = 'https://api.weixin.qq.com/cgi-bin/user/get';
    //  黑名单列表
    const API_BLACK_LIST_URL = "https://api.weixin.qq.com/cgi-bin/tags/members/getblacklist";
    //  拉黑用户
    const API_SET_BLACK_URL = "https://api.weixin.qq.com/cgi-bin/tags/members/batchblacklist";
    //  取消拉黑
    const API_CANCEL_BLACK_URL = "https://api.weixin.qq.com/cgi-bin/tags/members/batchunblacklist";

    private $accessToken;

    public function init(){
        parent::init();
        $this->accessToken = (new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken();
    }

    /**
     * 根据openId获得一个会员的信息
     * @param $openId string
     * @link https://nai8.me
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140839
     * @author abei<abei@nai8.me>
     * @return mixed
     */
    public function info($openId){

        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_USER_INFO_URL."?access_token=".$this->accessToken."&openid=".$openId."&lang=zh_CN")
            ->setMethod('get')
            ->setFormat(Client::FORMAT_JSON)->send();

        return Json::decode($response->getContent());
    }

    /**
     * 批量获取会员信息
     */
    public function batchInfo($openIds = []){
        $userList = array_map(function($openId){
            return [
                'openid'=>$openId,
                'lang'=>'zh_CN'
            ];
        },$openIds);

        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_BATCH_USER_INFO_URL."?access_token=".$this->accessToken)
            ->setMethod('post')
            ->setData(['user_list'=>$userList])
            ->setFormat(Client::FORMAT_JSON)->send();

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        return $data['user_info_list'];
    }

    /**
     * 获得用户列表
     * 每次最多10000个
     *
     * @param $nextOpenId bool 第一个拉取的OPENID，不填默认从头开始拉取
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140840
     * @author abei<abei@nai8.me>
     */
    public function ls($nextOpenId = false){
        $openIdStr = $nextOpenId ? "&next_openid={$nextOpenId}" : '';

        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_USER_LIST_URL."?access_token={$this->accessToken}{$openIdStr}")
            ->setMethod('get')
            ->setFormat(Client::FORMAT_JSON)->send();

        return $response->getData();
    }

    /**
     * 获取公众号的黑名单列表
     * 该接口每次调用最多可拉取 10000 个OpenID，当列表数较多时，可以通过多次拉取的方式来满足需求。
     */
    public function blackList($beginOpenId = null){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_BLACK_LIST_URL."?access_token={$this->accessToken}")
            ->setMethod('post')
            ->setFormat(Client::FORMAT_JSON)
            ->setData(['begin_openid'=>$beginOpenId])->send();

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode']) <> 0){
            throw new Exception($data['errmsg'],$data['errcode']);
        }

        return $data;
    }

    /**
     * 拉黑用户
     * @param array $openIds
     * @return Exception | boolean
     * @throws Exception
     */
    public function setBlacks($openIds = []){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_SET_BLACK_URL."?access_token={$this->accessToken}")
            ->setMethod('post')
            ->setFormat(Client::FORMAT_JSON)
            ->setData(['openid_list'=>$openIds])->send();

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode']) <> 0){
            throw new Exception($data['errmsg'],$data['errcode']);
        }

        return true;
    }

    /**
     * 取消拉黑用户
     */
    public function cancelBlacks($openIds = []){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_CANCEL_BLACK_URL."?access_token={$this->accessToken}")
            ->setMethod('post')
            ->setFormat(Client::FORMAT_JSON)
            ->setData(['openid_list'=>$openIds])->send();

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode']) <> 0){
            throw new Exception($data['errmsg'],$data['errcode']);
        }

        return true;
    }

}