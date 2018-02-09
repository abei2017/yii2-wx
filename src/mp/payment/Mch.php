<?php
namespace abei2017\wx\mp\payment;

use abei2017\wx\core\Exception;
use Yii;
use abei2017\wx\core\Driver;
use abei2017\wx\helpers\Xml;
use yii\httpclient\Client;

class Mch extends Driver {

    const API_SEND_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
    const API_QUERY_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo';

    /**
     * 发送
     * @param $params array
     * $params = [
     *  'partner_trade_no'=>'xxx',// 必填
     *  'openid'=>'xxx',// 必填
     *  'amount'=>'xxx',// 必填
     *  'desc'=>'xxx',// 必填
     *  'check_name'=>'NO_CHECK',// 必填 或FORCE_CHECK
     * ]
     * @throws Exception
     */
    public function send($params = []){
        $conf = [
            'mch_appid'=>$this->conf['app_id'],
            'mchid'=>$this->conf['payment']['mch_id'],
            'spbill_create_ip'=>Yii::$app->request->userIP,
            'nonce_str'=>Yii::$app->security->generateRandomString(32)
        ];
        $params = array_merge($params,$conf);
        $params['sign'] = $this->makeSign($params);

        $certs = [
            'SSLCERT' => $this->conf['payment']['cert_path'],
            'SSLKEY' => $this->conf['payment']['key_path'],
        ];

        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_SEND_URL)
            ->setMethod('post')
            ->setData($params)
            ->setFormat(Client::FORMAT_XML)
            ->setOptions([
                CURLOPT_SSLCERTTYPE=>'PEM',
                CURLOPT_SSLCERT=>$certs['SSLCERT'],
                CURLOPT_SSLKEYTYPE=>'PEM',
                CURLOPT_SSLKEY=>$certs['SSLKEY'],
            ])
            ->send();

        if($response->isOk == false){
            throw new Exception('无响应');
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
     * @author abei<abei@nai8.me>
     * @link https://nai8.me
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
        $params['sign'] = $this->makeSign($params);

        $certs = [
            'SSLCERT' => $this->conf['payment']['cert_path'],
            'SSLKEY' => $this->conf['payment']['key_path'],
        ];

        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_QUERY_URL)
            ->setMethod('post')
            ->setData($params)
            ->setFormat(Client::FORMAT_XML)
            ->setOptions([
                CURLOPT_SSLCERTTYPE=>'PEM',
                CURLOPT_SSLCERT=>$certs['SSLCERT'],
                CURLOPT_SSLKEYTYPE=>'PEM',
                CURLOPT_SSLKEY=>$certs['SSLKEY'],
            ])
            ->send();

        if($response->isOk == false){
            throw new Exception('无响应');
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

    private function makeSign($params){
        ksort($params);
        $str = $this->toUrlParams($params);
        $str .= "&key=".$this->conf['payment']['key'];
        return strtoupper(md5($str));
    }

    private function toUrlParams($vals){
        $buff = "";
        foreach($vals as $k=>$v){
            if($k != "sign" && $v != "" && is_array($v) == false){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff,"&");
        return $buff;
    }
}