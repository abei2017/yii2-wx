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

use Yii;
use abei2017\wx\core\Driver;
use yii\httpclient\Client;
use abei2017\wx\core\Exception;
use abei2017\wx\helpers\Util;

/**
 * Redbag
 * 现金红包接口
 * @package abei2017\wx\mp\payment
 * @link https://nai8.me/yii2wx
 * @author abei<abei@nai8.me>
 */
class Redbag extends Driver {

    /**
     * 发送普通红包
     * @var
     */
    const API_SEND_NORMAl_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';

    /**
     * 发送裂变红包
     * @var
     */
    const API_SEND_GROUP_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendgroupredpack';

    /**
     * 查询红包列表
     * @var
     */
    const API_QUERY_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gethbinfo';

    /**
     * 发送一个红包
     * @param $params
     * @param $type string 红包类型
     * @throws Exception
     * @return array
     */
    public function send($params,$type = 'normal'){
        $conf = [
            'nonce_str'=>Yii::$app->security->generateRandomString(32),
            'mch_id'=>$this->conf['payment']['mch_id'],
            'wxappid'=>$this->conf['app_id'],
        ];

        if($type == 'group'){
            $conf['amt_type'] = 'ALL_RAND';
        }else{
            $conf['client_ip'] = Yii::$app->request->userIP;
        }

        $params = array_merge($params,$conf);
        $params['sign'] = Util::makeSign($params,$this->conf['payment']['key']);

        $options = [
            CURLOPT_SSLCERTTYPE=>'PEM',
            CURLOPT_SSLCERT=>$this->conf['payment']['cert_path'],
            CURLOPT_SSLKEYTYPE=>'PEM',
            CURLOPT_SSLKEY=>$this->conf['payment']['key_path'],
        ];

        $response = $this->post($type == 'normal' ? self::API_SEND_NORMAl_URL : self::API_SEND_GROUP_URL,$params,[],$options)
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
     * 获取一个红包信息
     * @param $mchBillno string 商户订单号
     * @throws Exception
     * @return object
     */
    public function query($mchBillno){
        $params = [
            'appid'=>$this->conf['app_id'],
            'mch_id'=>$this->conf['payment']['mch_id'],
            'mch_billno'=>$mchBillno,
            'bill_type'=>'MCHT',
            'nonce_str'=>Yii::$app->security->generateRandomString(32)
        ];
        $params['sign'] = Util::makeSign($params,$this->conf['payment']['key']);

        $options = [
            CURLOPT_SSLCERTTYPE=>'PEM',
            CURLOPT_SSLCERT=>$this->conf['payment']['cert_path'],
            CURLOPT_SSLKEYTYPE=>'PEM',
            CURLOPT_SSLKEY=>$this->conf['payment']['key_path'],
        ];

        $response = $this->post(self::API_QUERY_URL,$params,[],$options)
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
}