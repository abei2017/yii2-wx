<?php
/*
 * This file is part of the abei2017/yii2-wx
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx\mp\user;

use abei2017\wx\core\Driver;
use abei2017\wx\core\AccessToken;
use abei2017\wx\core\Exception;
use yii\httpclient\Client;

/**
 * User
 * 用户管理助手
 * @package abei2017\wx\mp\user
 * @link https://nai8.me/yii2wx
 * @author abei<abei@nai8.me>
 */
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
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140839
     * @return mixed
     * @throws Exception
     */
    public function info($openId){
        $response = $this->get(self::API_USER_INFO_URL."?access_token=".$this->accessToken."&openid=".$openId."&lang=zh_CN")
            ->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_JSON);
        return $response->getData();
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

        $response = $this->post(self::API_BATCH_USER_INFO_URL."?access_token=".$this->accessToken,[
            'user_list'=>$userList
        ])->setFormat(Client::FORMAT_JSON)->send();

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
     */
    public function ls($nextOpenId = false){
        $openIdStr = $nextOpenId ? "&next_openid={$nextOpenId}" : '';
        $response = $this->get(self::API_USER_LIST_URL."?access_token={$this->accessToken}{$openIdStr}")
            ->send();

        return $response->getData();
    }

    /**
     * 获取公众号的黑名单列表
     * 该接口每次调用最多可拉取 10000 个OpenID，当列表数较多时，可以通过多次拉取的方式来满足需求。
     */
    public function blackList($beginOpenId = null){
        $response = $this->post(self::API_BLACK_LIST_URL."?access_token={$this->accessToken}",['begin_openid'=>$beginOpenId])
            ->setFormat(Client::FORMAT_JSON)->send();

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode']) && $data['errcode'] <> 0){
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
        $response = $this->post(self::API_SET_BLACK_URL."?access_token={$this->accessToken}",['openid_list'=>$openIds])
            ->setFormat(Client::FORMAT_JSON)->send();

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode']) && $data['errcode'] <> 0){
            throw new Exception($data['errmsg'],$data['errcode']);
        }

        return true;
    }

    /**
     * 取消拉黑用户
     */
    public function cancelBlacks($openIds = []){
        $response = $this->post(self::API_CANCEL_BLACK_URL."?access_token={$this->accessToken}",['openid_list'=>$openIds])
            ->setFormat(Client::FORMAT_JSON)->send();

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode']) && $data['errcode'] <> 0){
            throw new Exception($data['errmsg'],$data['errcode']);
        }

        return true;
    }

}