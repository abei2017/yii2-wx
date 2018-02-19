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
 * 用户标签管理助手
 *
 * 开发者可以使用用户标签管理的相关接口，实现对公众号的标签进行创建、查询、修改、删除等操作，也可以对用户进行打标签、取消标签等操作。
 * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140837
 * @author abei<abei@nai8.me>
 * @package abei2017\wx\mp\user
 */
class Tag extends Driver {

    /*
     * 创建标签API
     */
    const API_CREATE_URL = "https://api.weixin.qq.com/cgi-bin/tags/create";

    /**
     * 公众号已创建的标签API
     */
    const API_LIST_URL = "https://api.weixin.qq.com/cgi-bin/tags/get";

    /**
     * 编辑标签API
     */
    const API_UPDATE_URL = "https://api.weixin.qq.com/cgi-bin/tags/update";

    /**
     * 删除标签API
     */
    const API_DELETE_URL = "https://api.weixin.qq.com/cgi-bin/tags/delete";

    /**
     * 一个标签下的粉丝API
     */
    const API_FOLLOWERS_URL = "https://api.weixin.qq.com/cgi-bin/user/tag/get";

    /**
     * 批量为用户打标签
     */
    const API_BATCH_TAG_URL = "https://api.weixin.qq.com/cgi-bin/tags/members/batchtagging";

    /**
     * 批量为用户取消标签
     */
    const API_UN_BATCH_TAG_URL = "https://api.weixin.qq.com/cgi-bin/tags/members/batchuntagging";

    /**
     * 一个用户标签列表API
     */
    const API_USER_TAGS_URL = "https://api.weixin.qq.com/cgi-bin/tags/getidlist";

    /**
     * @var bool 接口令牌
     */
    private $accessToken = false;

    /**
     * @var array
     */
    static $errors = [
        -1 => '系统繁忙',
        45157 => '标签名非法，请注意不能和其他标签重名。#45157 ',
        45158 => '标签名长度超过30个字节。#45158',
        45056 => '创建的标签数过多，请注意不能超过100个。#45056',
        45058 => '不能修改0/1/2这三个系统默认保留的标签。#45058',
        45057 => '该标签下粉丝数超过10w，不允许直接删除。#45057',
        40003 => '传入非法的openid。#40003',
        45159 => '非法的tag_id。#45159',
        40032 => '每次传入的openid列表个数不能超过50个。#45159',
        45059 => '有粉丝身上的标签数已经超过限制，即超过20个。#45059',
        49003 => '传入的openid不属于此AppID。#49003',
    ];

    public function init(){
        parent::init();
        $this->accessToken = (new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient,'extra'=>[]]))->getToken();
    }

    /**
     * 生成一组标签
     * 每次创建一个，最多只能创建100个标签。
     * @param $tag string 要建立的标签
     * @throws Exception
     */
    public function create($tag){
        $this->httpClient->formatters = ['uncodeJson'=>'abei2017\wx\helpers\JsonFormatter'];
        $response = $this->post(self::API_CREATE_URL."?access_token={$this->accessToken}",['tag'=>['name'=>$tag]])
            ->setFormat('uncodeJson')->send();

        $data = $response->getData();
        if(isset($data['errcode'])){
            throw new Exception(self::$errors[$data['errcode']],$data['errcode']);
        }

        return $data['tag'];
    }

    /**
     * 已经建立的标签列表
     * @return mixed
     */
    public function ls(){
        $response = $this->get(self::API_LIST_URL."?access_token={$this->accessToken}")->send();

        $data = $response->getData();
        return $data['tags'];
    }

    /**
     * 编辑一个已经存在的标签
     * @param $tagId integer 要编辑的tag的id
     * @param $newName string 新标签的名字
     * @return boolean
     * @throws Exception
     */
    public function update($tagId,$newName){
        $this->httpClient->formatters = ['uncodeJson'=>'abei2017\wx\helpers\JsonFormatter'];
        $response = $this->post(self::API_UPDATE_URL."?access_token={$this->accessToken}",[
            'tag'=>['id'=>$tagId,'name'=>$newName]
        ])->setFormat('uncodeJson')->send();

        $data = $response->getData();
        if(isset($data['errcode']) && $data['errcode'] !== 0){
            throw new Exception(self::$errors[$data['errcode']],$data['errcode']);
        }

        return true;
    }

    /**
     * 删除一个标签
     * @param $tagId integer 标签ID
     * @return boolean
     * @throws Exception
     */
    public function delete($tagId){
        $response = $this->post(self::API_DELETE_URL."?access_token={$this->accessToken}",[
            'tag'=>['id'=>$tagId]
        ])->setFormat(Client::FORMAT_JSON)->send();

        $data = $response->getData();
        if(isset($data['errcode']) && $data['errcode'] !== 0){
            throw new Exception(self::$errors[$data['errcode']],$data['errcode']);
        }

        return true;
    }

    /**
     * 获取标签下粉丝列表
     * @param $tagId integer 标签ID
     * @param $nextOpenId string
     * @return boolean
     * @throws Exception
     */
    public function followers($tagId,$nextOpenId = ""){
        $response = $this->post(self::API_FOLLOWERS_URL."?access_token={$this->accessToken}",['tagid'=>$tagId,'next_openid'=>$nextOpenId])
            ->setFormat(Client::FORMAT_JSON)->send();

        $data = $response->getData();
        return $data;
    }

    /**
     * 批量为一个用户打标签
     * 目前支持公众号为用户打上最多20个标签。
     */
    public function batchTagToUser($openIds,$tagId){
        $response = $this->post(self::API_BATCH_TAG_URL."?access_token={$this->accessToken}",['openid_list'=>$openIds,'tag_id'=>$tagId])
            ->setFormat(Client::FORMAT_JSON)->send();

        $data = $response->getData();

        if(isset($data['errcode']) && $data['errcode'] == 0){
            return true;
        }else{
            throw new Exception(self::$errors[$data['errcode']],$data['errcode']);
        }
    }

    /**
     * 批量为用户取消标签
     */
    public function unBatchTagFromUser($openIds,$tagId){
        $response = $this->post(self::API_UN_BATCH_TAG_URL."?access_token={$this->accessToken}",['openid_list'=>$openIds,'tag_id'=>$tagId])
            ->setFormat(Client::FORMAT_JSON)->send();

        $data = $response->getData();

        if(isset($data['errcode']) && $data['errcode'] == 0){
            return true;
        }else{
            throw new Exception(self::$errors[$data['errcode']],$data['errcode']);
        }
    }

    /**
     * 获得一个用户上的标签列表
     * @param $openId string 用户的openId
     * @return array
     */
    public function userTags($openId){
        $response = $this->post(self::API_USER_TAGS_URL."?access_token={$this->accessToken}",['openid'=>$openId])
            ->setFormat(Client::FORMAT_JSON)->send();
        $data = $response->getData();

        return $data['tagid_list'];
    }

}