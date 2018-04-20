<?php
/*
 * This file is part of the abei2017/yii2-wx
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx\mini\payment;

use abei2017\wx\core\Driver;
use Yii;
use yii\base\Response;
use abei2017\wx\core\Exception;
use yii\httpclient\Client;
use abei2017\wx\helpers\Util;
use abei2017\wx\helpers\Xml;

/**
 * Pay.
 * 小程序支付功能
 * @author abei<abei@nai8.me>
 * @link https://nai8.me/yii2wx
 * @package abei2017\wx\mini\payment
 */
class Pay extends Driver {

    /**
     * 预付订单街口地址
     */
    const PREPARE_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    /**
     * Prepare
     * @var
     */
    private $prepare;

    /**
     * 生成预备订单
     *
     * @param $attrs array
     * @link http://nai8.me/ext-mini.html
     * @throws Exception
     * @return object
     */
    protected function prepare($attrs = []){

        if(empty($attrs['out_trade_no'])){
            throw new Exception('缺少统一支付接口必填参数out_trade_no！');
        }elseif (empty($attrs['body'])){
            throw new Exception('缺少统一支付接口必填参数body！');
        }elseif (empty($attrs['total_fee'])){
            throw new Exception('缺少统一支付接口必填参数total_fee！');
        }elseif (empty($attrs['trade_type'])){
            throw new Exception('缺少统一支付接口必填参数trade_type！');
        }elseif (empty($attrs['openid'])){
            throw new Exception('统一支付接口中，缺少必填参数openid！');
        }

        if(empty($attrs['notify_url'])){
            throw new Exception('异步通知地址不能为空');
        }

        $attrs['appid'] = $this->conf['app_id'];
        $attrs['mch_id'] = $this->conf['payment']['mch_id'];
        $attrs['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];
        $attrs['nonce_str'] = Yii::$app->security->generateRandomString(32);
        $attrs['sign'] = Util::makeSign($attrs,$this->conf['payment']['key']);

        $response = $this->post(self::PREPARE_URL,$attrs)->setFormat(Client::FORMAT_XML)->send();
        return $this->prepare = (object)$response->getData();
    }

    /**
     * jsapi类型的支付
     * @param array $attributes
     * @return object prepare
     */
    public function jsApi($attributes = []){
        $attributes['trade_type'] = 'JSAPI';
        $result = $this->prepare($attributes);
        return $result;
    }

    /**
     * 生成一个jsapi类型的配置
     *
     * @param $prepayId string
     * @author abei<abei@nai8.me>
     * @link http://nai8.me/ext-mini.html
     * @return array
     */
    public function configForPayment($prepayId){
        $params = [
            'appId' => $this->conf['app_id'],
            'timeStamp' => strval(time()),
            'nonceStr' => uniqid(),
            'package' => "prepay_id=$prepayId",
            'signType' => 'MD5',
        ];

        $params['paySign'] = Util::makeSign($params,$this->conf['payment']['key']);

        return $params;
    }

    public function handleNotify(callable $callback){
        $notify = $this->getNotify();

        if (!$notify->checkSign()) {
            throw new Exception('签名错误');
        }

        $notify = $notify->getData();
        $isSuccess = $notify['result_code'] === 'SUCCESS';

        $handleResult = call_user_func_array($callback, [$notify, $isSuccess]);

        if (is_bool($handleResult) && $handleResult) {
            $response = [
                'return_code' => 'SUCCESS',
                'return_msg' => 'OK',
            ];
        } else {
            $response = [
                'return_code' => 'FAIL',
                'return_msg' => $handleResult,
            ];
        }

        return Xml::build($response);
    }

    public function getNotify(){
        return new Notify($this->conf['payment']);
    }
}