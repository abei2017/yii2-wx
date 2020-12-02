<?php
/*
 * This file is part of the abei2017/yii2-wx
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx\mini\security;

use abei2017\wx\core\Driver;
use abei2017\wx\core\AccessToken;
use abei2017\wx\core\Exception;

/**
 * Security
 * 内容安全
 * @author abei<abei@nai8.me>
 * @link https://nai8.me/app-2.html
 * @link https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/sec-check/security.imgSecCheck.html
 * @package abei2017\wx\mini\security
 */
class Security extends Driver {

    //  文本是否含有违法违规内容
    const API_MSG_SEC_CHECK = 'https://api.weixin.qq.com/wxa/msg_sec_check';

    // 图片是否含有违法违规内容
    const API_IMG_SEC_CHECK = 'https://api.weixin.qq.com/wxa/img_sec_check';

    // 检测媒体是否有违规内容
    const API_MEDIA_CHECK_ASYNC = 'https://api.weixin.qq.com/wxa/media_check_async';

    private $accessToken = null;

    public function init(){
        parent::init();
        $this->accessToken = (new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken();
    }

    /**
     * 检查一段文本是否含有违法违规内容
     *
     * @param $content string 待检查的文本的内容
     * @since 1.3.6
     */
    public function checkText($content){
        $this->httpClient->formatters = ['uncodeJson'=>'abei2017\wx\helpers\JsonFormatter'];
        $params = ['content'=>$content];    
        $response = $this->post(self::API_MSG_SEC_CHECK."?access_token=".$this->accessToken,$params)
            ->setFormat('uncodeJson')->send();    

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        return $response->getData();    
    }

    /**
     * 校验一张图片是否含有违法违规内容。
     *
     * @param $path string 图片在服务器上的绝对路径（格式支持PNG、JPEG、JPG、GIF，图片尺寸不超过 750px x 1334px）
     * @since 1.3.6
     */
    public function checkImage($path){
        $this->httpClient->formatters = ['uncodeJson'=>'abei2017\wx\helpers\JsonFormatter'];
        $response = $this->post(self::API_IMG_SEC_CHECK."?access_token={$this->accessToken}")
            ->addFile('media', $path)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        return $response->getData();     
    }

    /**
     * 异步校验图片/音频是否含有违法违规内容。
     * 
     * @param $url string 可访问/下载的URL地址
     * @param $type string audio/image 要检查的媒体类型
     * @since 1.3.6
     */
    public function checkMediaByAsync($url,$type = 'audio'){
        $this->httpClient->formatters = ['uncodeJson'=>'abei2017\wx\helpers\JsonFormatter'];
        
        $typeTypes = [
            'audio'=>1,
            'image'=>2
        ];

        $params = ['media_url'=>$url,'media_type'=>$typeTypes[$type]];    
        $response = $this->post(self::API_MEDIA_CHECK_ASYNC."?access_token=".$this->accessToken,$params)
            ->setFormat('uncodeJson')->send();    

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        return $response->getData();  
    }
}