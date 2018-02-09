<?php
namespace abei2017\wx\mp\payment;

use Yii;
use abei2017\wx\core\Driver;
use yii\httpclient\Client;
use abei2017\wx\core\Exception;

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
        $params['sign'] = $this->makeSign($params);

        $certs = [
            'SSLCERT' => $this->conf['payment']['cert_path'],
            'SSLKEY' => $this->conf['payment']['key_path'],
        ];

        $response = $this->httpClient->createRequest()
            ->setUrl($type == 'normal' ? self::API_SEND_NORMAl_URL : self::API_SEND_GROUP_URL)
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
     * 获取一个红包信息
     * @param $mchBillno string 商户订单号
     */
    public function query($mchBillno){
        $params = [
            'appid'=>$this->conf['app_id'],
            'mch_id'=>$this->conf['payment']['mch_id'],
            'mch_billno'=>$mchBillno,
            'bill_type'=>'MCHT',
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