<?php
/*
 * This file is part of the abei2017/yii2-wx
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx\mp\qrcode;

use abei2017\wx\core\Driver;
use abei2017\wx\core\AccessToken;
use yii\helpers\Json;
use yii\httpclient\Client;
use abei2017\wx\helpers\Util;

/**
 * Qrcode
 * 二维码生成接口
 * @package abei2017\wx\mp\qrcode
 * @link https://nai8.me/yii2wx
 * @author abei<abei@nai8.me>
 */
class Qrcode extends Driver {

    private $accessToken;

    //  接口地址
    const API_QRCODE_URL = 'https://api.weixin.qq.com/cgi-bin/qrcode/create';

    public function init()
    {
        parent::init();
        $this->accessToken = (new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken();
    }

    /**
     * 生成临时二维码
     *
     * @param $seconds integer 过期秒数
     * @param $val integer 二维码的值
     * @author abei<abei@nai8.me>
     * @return array
     */
    public function intTemp($seconds = 2592000,$val){
        return $this->temp('QR_SCENE',$seconds,['scene_id'=>$val]);
    }

    public function strTemp($seconds = 2592000,$val){
        return $this->temp('QR_STR_SCENE',$seconds,['scene_str'=>$val]);
    }

    private function temp($action = 'QR_SCENE', $seconds = 2592000, $scene = ['scene_id'=>0]){
        if((int)$seconds > 2592000){
            throw new Exception('临时二维码有效期最多只能2592000秒（30天）');
        }

        if((int)$seconds <= 0){
            //  如果填写不正确则默认为60秒，这样做主要是适配一些扫码登录功能。by abei
            $seconds = 60;
        }

        $params = array_merge(['expire_seconds'=>$seconds,'action_name'=>$action,'action_info'=>['scene'=>$scene]]);

        $response = $this->post(Qrcode::API_QRCODE_URL."?access_token={$this->accessToken}",$params)
            ->setFormat(Client::FORMAT_JSON)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode'])){
            throw new Exception("{$data['errcode']}#{$data['errmsg']}");
        }

        return $data;
    }

    /**
     * 生成永久的数字型二维码
     * @param $val integer
     * @return mixed
     */
    public function intForver($val){
        return $this->forver('QR_LIMIT_SCENE',['scene_id'=>$val]);
    }

    /**
     * 生成永久的字符型二维码
     * @param $val
     * @return mixed
     */
    public function strForver($val){
        return $this->forver('QR_LIMIT_STR_SCENE',['scene_str'=>$val]);
    }

    /**
     * 生成永久二维码
     * @param string $action
     * @param array $scene
     * @return mixed
     */
    private function forver($action = 'QR_LIMIT_SCENE', $scene = ['scene_id'=>0]){
        $params = array_merge(['action_name'=>$action,'action_info'=>['scene'=>$scene]]);

        $response = $this->post(Qrcode::API_QRCODE_URL."?access_token={$this->accessToken}",$params)
            ->setFormat(Client::FORMAT_JSON)->send();

        $response->setFormat(Client::FORMAT_JSON);

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode'])){
            throw new Exception($data['errmsg'],$data['errcode']);
        }

        return $data;
    }
}