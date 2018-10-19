<?php

/*
 * This file is part of the abei2017/yii2-wx.
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx\mini\custom;

use abei2017\wx\core\Driver;
use abei2017\wx\core\AccessToken;
use Yii;
use yii\httpclient\Client;

/**
 * Customer
 * 此类负责模块其他类的驱动以及相关变量的初始化
 *
 * @link https://nai8.me/study/
 * @author abei<abei@nai8.me>
 * @package abei2017\wx
 */

class Customer extends Driver {

    private $accessToken;

    const CUSTOMER_TYPING = "https://api.weixin.qq.com/cgi-bin/message/custom/typing";
    const TEMP_MEDIA = "https://api.weixin.qq.com/cgi-bin/media/get";
    const SEND_MESSAGE = "https://api.weixin.qq.com/cgi-bin/message/custom/send";
    const UPLOAD_MEDIA = "https://api.weixin.qq.com/cgi-bin/media/upload";

    public function init() {

        parent::init();
        $this->accessToken = (new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken();
    }


    /**
     * 下发客服当前输入状态给用户
     * @param $openId string 用户的 OpenID
     * @param $command Typing / CancelTyping
     * @since 1.2
     */
    public function typing($openId,$command){
        $params = [
            'touser'=>$openId,
            'command'=>$command
        ];
        $response = $this->post(self::CUSTOMER_TYPING."?access_token=".$this->accessToken,$params)
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

    /**
     * 获得临时素材
     * 获取客服消息内的临时素材（即下载临时的多媒体文件）。目前小程序仅支持下载图片文件。
     *
     * @param $mediaId string 媒体文件ID
     * @since 1.2
     */
    public function getMedia($mediaId){
        $response = $this->get(self::TEMP_MEDIA."?access_token={$this->accessToken}&media_id={$mediaId}")
            ->send();

        $stream = $response->getContent();
        return $stream;
    }

    /**
     * 发送客服消息给用户
     *
     * @param $openId string    发送给谁
     * @param $msgType string   消息类型
     * @param $content string | array   消息内容
     * @since 1.2
     */
    public function send($openId,$msgType = 'text',$data){
        switch ($msgType){
            case 'text':
                $data = ['text'=>['content'=>$data]];
                break;
            case 'image':
                $data = ['image'=>['media_id'=>$data]];
                break;
            case 'link':
                $data = ['link'=>$data];
                break;
            case 'miniprogrampage':
                $data = ['miniprogrampage'=>$data];
                break;
        }

        //  post数据
        $params = [
            'touser'=>$openId,
            'msgtype'=>$msgType
        ];

        $this->httpClient->formatters = ['uncodeJson'=>'abei2017\wx\helpers\JsonFormatter'];
        $response = $this->post(self::SEND_MESSAGE."?access_token={$this->accessToken}",array_merge($params,$data))
            ->setFormat('uncodeJson')
            ->send();

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

    /**
     * 上传素材
     * 小程序可以使用本接口把媒体文件（目前仅支持图片）上传到微信服务器，
     * 用户发送客服消息或被动回复用户消息。
     *
     * @param $file string 文件
     * @param $type string 文件类型
     * @since 1.2
     */
    public function upload($file,$type = 'image'){
        $response = $this->post(self::UPLOAD_MEDIA."?access_token={$this->accessToken}&type={$type}")
            ->addFile('media', $file)->send();

        $response->setFormat(Client::FORMAT_JSON);
        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $result = $response->getData();
        if(isset($result['errcode']) && $result['errcode'] != 0){
            throw new Exception($result['errmsg'],$result['errcode']);
        }

        return $result['media_id'];
    }
}