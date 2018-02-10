<?php
/*
 * This file is part of the abei2017/yii2-wx
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx\mp\payment;

use abei2017\wx\core\Exception;
use Yii;
use abei2017\wx\core\Driver;
use yii\httpclient\Client;
use abei2017\wx\helpers\Util;

/**
 * Mch
 * 企业付款接口
 *
 * @author abei<abei@nai8.me>
 * @link http://nai8.me/yii2wx
 * @package abei2017\wx\mp\payment
 */
class Mch extends Driver {

    const API_SEND_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
    const API_QUERY_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo';

    /**
     * 发送企业付款到零钱包
     *
     * @param $params array 付款参数（必填参数为partner_trade_no、openid、amount、desc、check_name）
     * @throws Exception
     * @return array
     */
    public function send($params = []){
        $conf = [
            'mch_appid'=>$this->conf['app_id'],
            'mchid'=>$this->conf['payment']['mch_id'],
            'spbill_create_ip'=>Yii::$app->request->userIP,
            'nonce_str'=>Yii::$app->security->generateRandomString(32)
        ];
        $params = array_merge($params,$conf);
        $params['sign'] = Util::makeSign($params,$this->conf['payment']['key']);

        $options = [
            CURLOPT_SSLCERTTYPE=>'PEM',
            CURLOPT_SSLCERT=>$this->conf['payment']['cert_path'],
            CURLOPT_SSLKEYTYPE=>'PEM',
            CURLOPT_SSLKEY=>$this->conf['payment']['key_path'],
        ];

        $response = $this->post(self::API_SEND_URL,$params,[],$options)
            ->setFormat(Client::FORMAT_XML)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_XML);
        $result = $response->getData();

        if($result['return_code'] == 'FAIL'){
            throw new Exception($result['return_msg']);
        }

        if($result['result_code'] == 'FAIL'){
            throw new Exception($result['err_code']."#".$result['err_code_des']);
        }

        return $result;
    }

    /**
     * 查询企业付款
     * 只支持查询30天内的订单，30天之前的订单请登录商户平台查询。
     *
     * @param $partnerTradeNo string 商户订单号
     * @return array
     * @throws Exception
     */
    public function query($partnerTradeNo){
        $params = [
            'appid'=>$this->conf['app_id'],
            'mch_id'=>$this->conf['payment']['mch_id'],
            'partner_trade_no'=>$partnerTradeNo,
            'nonce_str'=>Yii::$app->security->generateRandomString(32)
        ];
        $params['sign'] = Util::makeSign($params,$this->conf['payment']['key']);

        $options = [
            CURLOPT_SSLCERTTYPE=>'PEM',
            CURLOPT_SSLCERT=>$this->conf['payment']['cert_path'],
            CURLOPT_SSLKEYTYPE=>'PEM',
            CURLOPT_SSLKEY=>$this->conf['payment']['key_path'],
        ];

        $response = $this->post(self::API_QUERY_URL,$params,[],$options)->setFormat(Client::FORMAT_XML)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_XML);
        $result = $response->getData();

        if($result['return_code'] == 'FAIL'){
            throw new Exception($result['return_msg']);
        }

        if($result['result_code'] == 'FAIL'){
            throw new Exception($result['err_code']."#".$result['err_code_des']);
        }

        return $result;
    }
}