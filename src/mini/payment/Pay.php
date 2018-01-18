<?php
/*
 * This file is part of the abei2017/yii2-mini-program.
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

/**
 * Class Pay.
 *
 * @package abei2017\mini\pay
 */
class Pay extends Driver {

    /**
     * 预付订单街口地址
     */
    const PREPARE_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    /**
     * @var Prepare result.
     */
    private $prepare;

    /**
     * 生成预备订单
     *
     * @param $attrs array
     * @author abei<abei@nai8.me>
     * @link http://nai8.me/ext-mini.html
     * @throws PayException
     * @return object
     */
    protected function prepare($attrs = []){

        if(empty($attrs['out_trade_no'])){
            throw new PayException('缺少统一支付接口必填参数out_trade_no！');
        }elseif (empty($attrs['body'])){
            throw new PayException('缺少统一支付接口必填参数body！');
        }elseif (empty($attrs['total_fee'])){
            throw new PayException('缺少统一支付接口必填参数total_fee！');
        }elseif (empty($attrs['trade_type'])){
            throw new PayException('缺少统一支付接口必填参数trade_type！');
        }elseif (empty($attrs['openid'])){
            throw new PayException('统一支付接口中，缺少必填参数openid！');
        }

        if(empty($attrs['notify_url'])){
            throw new PayException('异步通知地址不能为空');
        }

        $attrs['appid'] = $this->conf['app_id'];
        $attrs['mch_id'] = $this->conf['payment']['mch_id'];
        $attrs['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];
        $attrs['nonce_str'] = Yii::$app->security->generateRandomString(32);
        $attrs['sign'] = $this->makeSign($attrs);

        $xml = $this->toXml($attrs);

        $request = $this->httpClient->createRequest()
            ->setUrl(self::PREPARE_URL)
            ->setMethod('post')
            ->setOptions([
                CURLOPT_POSTFIELDS => $xml
            ]);

        $response = $request->send();
        return $this->prepare = (object)$response->getData();
    }

    private function toUrlParams($vals){
        $buff = "";
        foreach ($vals as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    private function toXml($vals)
    {
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

    private function makeSign($vals){
        ksort($vals);
        $string = $this->toUrlParams($vals);
        $string = $string . "&key=".$this->conf['payment']['key'];
        $string = md5($string);
        $result = strtoupper($string);
        return $result;
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

        $params['paySign'] = $this->makeSign($params);

        return $params;
    }

    /**
     * Handle pay notify.
     *
     * @param callable $callback
     */
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


    }

    public function getNotify(){
        return new Notify($this->conf['payment']);
    }
}