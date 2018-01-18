<?php
namespace abei2017\wx\mp\payment;

use Yii;
use abei2017\wx\core\Driver;
use yii\httpclient\Client;

class Pay extends Driver {

    /**
     * 预付订单街口地址
     */
    const PREPARE_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    private $prepare;

    /**
     * @param array $attributes
     * @throws PayException
     */
    protected function prepare($attributes = []){
        if(empty($attributes['out_trade_no'])){
            throw new PayException('缺少统一支付接口必填参数out_trade_no！');
        }elseif (empty($attributes['body'])){
            throw new PayException('缺少统一支付接口必填参数body！');
        }elseif (empty($attributes['total_fee'])){
            throw new PayException('缺少统一支付接口必填参数total_fee！');
        }elseif (empty($attributes['trade_type'])){
            throw new PayException('缺少统一支付接口必填参数trade_type！');
        }

        if(empty($attributes['notify_url'])){
            throw new PayException('异步通知地址不能为空');
        }

        $attributes['appid'] = $this->conf['app_id'];
        $attributes['mch_id'] = $this->conf['payment']['mch_id'];
        $attributes['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];
        $attributes['nonce_str'] = Yii::$app->security->generateRandomString(32);
        $attributes['sign'] = $this->makeSign($attributes);

        $xml = $this->toXml($attributes);

        $response = $this->httpClient->createRequest()
            ->setUrl(self::PREPARE_URL)
            ->setMethod('post')
            ->setOptions([
                CURLOPT_POSTFIELDS => $xml
            ])->send();

        $response->setFormat(Client::FORMAT_XML);

        return $this->prepare = $response->getData();
    }

    private function makeSign($vals){
        ksort($vals);
        $str = $this->toUrlParams($vals);
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

    private function toXml($vals){
        if(!is_array($vals)
            || count($vals) <= 0)
        {
            throw new PayException("数组数据异常！");
        }
        $xml = "<xml>";
        foreach ($vals as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * 原始扫码登录
     * @param $attributes array 原始扫码需要的参数
     * @return object
     */
    public function native($attributes = []){
        $attributes['trade_type'] = 'NATIVE';
        $result = $this->prepare($attributes);
        return $result;
    }

    /**
     * JSSDK支付
     * @param $attributes array JSSDK支付需要的参数
     */
    public function js($attributes = []){
        $attributes['trade_type'] = 'JSAPI';
        $result = $this->prepare($attributes);
        return $result;
    }

    public function configForPayment($prepayId){
        $params = [
            'appId' => $this->conf['app_id'],
            'timeStamp' => strval(time()),
            'nonceStr' => uniqid(),
            'package' => "prepay_id=$prepayId",
            'signType' => 'MD5',
        ];

        $params['paySign'] = $this->makeSign($params);

        return $params;
    }

    protected function getNotify(){
        return (new Notify(['merchant'=>$this->conf['payment']]));
    }

    public function handleNotify(callable $callback){
        $notify = $this->getNotify();

        if (!$notify->checkSign()) {
            throw new PayException('签名错误');
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

        return $response;
    }
}