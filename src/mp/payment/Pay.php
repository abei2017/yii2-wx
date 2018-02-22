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

use abei2017\wx\helpers\Xml;
use Yii;
use abei2017\wx\core\Driver;
use yii\httpclient\Client;
use abei2017\wx\core\Exception;
use abei2017\wx\helpers\Util;

/**
 * Pay
 * 微信支付助手
 *
 * @author abei<abei@nai8.me>
 * @link https://nai8.me/yii2wx
 * @package abei2017\wx\mp\payment
 */
class Pay extends Driver {

    const QUERY_REFUND_TRANSACTION_ID = 'transaction_id';
    const QUERY_REFUND_OUT_TRADE_NO = 'out_trade_no';
    const QUERY_REFUND_OUT_REFUND_NO = 'out_refund_no';
    const QUERY_REFUND_REFUND_ID = 'refund_no';

    /**
     * 返回所有订单信息，默认值
     */
    const TYPE_BILL_ALL = 'ALL';

    /**
     * 返回当日成功支付的订单
     */
    const TYPE_BILL_SUCCESS = 'SUCCESS';
    const TYPE_BILL_REFUND = 'REFUND';
    const TYPE_BILL_RECHARGE_REFUND = 'RECHARGE_REFUND';

    /**
     * 预付订单接口地址
     */
    const API_PREPARE_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    /**
     * 查询订单
     * @const
     */
    const API_QUERY_URL = 'https://api.mch.weixin.qq.com/pay/orderquery';

    /**
     * 关闭订单
     * @const
     */
    const API_CLOSE_URL = 'https://api.mch.weixin.qq.com/pay/closeorder';

    /**
     * 退款
     * @const
     */
    const API_REFUND_URL = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
    const API_REFUND_QUERY_URL = 'https://api.mch.weixin.qq.com/pay/refundquery';

    /**
     * 下载对账单
     * @const
     */
    const API_DOWNLOAD_BILL_URL = 'https://api.mch.weixin.qq.com/pay/downloadbill';

    /**
     * 转换短地址
     * @const
     */
    const API_SHORT_URL_URL = 'https://api.mch.weixin.qq.com/tools/ ';

    private $prepare;

    /**
     * 获得预支付订单
     * @param array $attributes
     * @throws Exception
     * @return object
     */
    protected function prepare($attributes = []){
        if(empty($attributes['out_trade_no'])){
            throw new Exception('缺少统一支付接口必填参数out_trade_no');
        }elseif (empty($attributes['body'])){
            throw new Exception('缺少统一支付接口必填参数body');
        }elseif (empty($attributes['total_fee'])){
            throw new Exception('缺少统一支付接口必填参数total_fee');
        }elseif (empty($attributes['trade_type'])){
            throw new Exception('缺少统一支付接口必填参数trade_type');
        }

        if(empty($attributes['notify_url'])){
            throw new Exception('异步通知地址不能为空');
        }

        $attributes['appid'] = $this->conf['app_id'];
        $attributes['mch_id'] = $this->conf['payment']['mch_id'];
        $attributes['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];
        $attributes['nonce_str'] = Yii::$app->security->generateRandomString(32);
        $attributes['sign'] = Util::makeSign($attributes,$this->conf['payment']['key']);

        $response = $this->post(self::API_PREPARE_URL,$attributes)
            ->setFormat(Client::FORMAT_XML)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_XML);
        return $this->prepare = $response->getData();
    }

    /**
     * 核查签名是否准确
     * @param $vals array 签名参数数组
     * @return bool
     */
    public function checkSign($vals){
        $sign = Util::makeSign($vals,$this->conf['payment']['key']);
        return $sign === $vals['sign'];
    }

    /**
     * 原始扫码支付
     * @param $attributes array 原始扫码需要的参数
     * @return object
     */
    public function native($attributes = []){
        $attributes['trade_type'] = 'NATIVE';
        $result = $this->prepare($attributes);
        return $result;
    }

    public function nativeDefinedQrcode($productId){
        $params = [
            'appid'=>$this->conf['app_id'],
            'mch_id'=>$this->conf['payment']['mch_id'],
            'time_stamp'=>time(),
            'nonce_str'=>Yii::$app->security->generateRandomString(32),
            'product_id'=>$productId,
        ];

        $sign = Util::makeSign($params,$this->conf['payment']['key']);

        $codeUrl = "weixin://wxpay/bizpayurl?appid={$params['appid']}&mch_id={$params['mch_id']}&nonce_str={$params['nonce_str']}&product_id={$productId}&time_stamp={$params['time_stamp']}&sign={$sign}";

        return urlencode($codeUrl);
    }

    public function nativeDefinedResponse($attributes){
        $attributes['trade_type'] = 'NATIVE';
        $prepare = $this->prepare($attributes);

        $responseParams = [
            'return_code'=>'SUCCESS',
            'appid'=>$this->conf['app_id'],
            'mch_id'=>$this->conf['payment']['mch_id'],
            'nonce_str'=>Yii::$app->security->generateRandomString(32),
            'prepay_id'=>$prepare['prepay_id'],
            'result_code'=>'SUCCESS',
        ];

        $responseParams['sign'] = Util::makeSign($responseParams,$this->conf['payment']['key']);

        return Xml::build($responseParams);
    }

    /**
     * JSSDK支付
     * @param $attributes array JSSDK支付需要的参数
     * @return object
     */
     public function js($attributes = []){
        $attributes['trade_type'] = 'JSAPI';
        $result = $this->prepare($attributes);
        return $result;
    }

    /**
     * 获得JSSDK支付中js端需要的参数
     * @param $prepayId
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

    /**
     * 获得支付结果通知的对象
     * @return Notify
     */
    protected function getNotify(){
        return (new Notify(['merchant'=>$this->conf['payment']]));
    }


    /**
     * 处理结果通知逻辑
     * @param callable $callback
     * @return string
     * @throws Exception
     */
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

    /**
     * 获得一个订单的信息
     * @param $outTradeNo string 商户订单号
     * @param bool $isTransaction 是否为微信订单号
     * @return mixed
     * @throws Exception
     */
    public function query($outTradeNo, $isTransaction = false){
        $params = [
            'appid'=>$this->conf['app_id'],
            'mch_id'=>$this->conf['payment']['mch_id'],
            'nonce_str'=>Yii::$app->security->generateRandomString(32),
            'sign_type'=>'MD5'
        ];

        if($isTransaction == true){
            $params['transaction_id'] = $outTradeNo;
        }else{
            $params['out_trade_no'] = $outTradeNo;
        }

        $params['sign'] = Util::makeSign($params,$this->conf['payment']['key']);

        $response = $this->post(self::API_QUERY_URL,$params)
            ->setFormat(Client::FORMAT_XML)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_XML);
        $result = $response->getData();

        return $result;
    }

    /**
     * 关闭订单
     * @param $outTradeNo string 商户订单号
     * @throws Exception
     * @return array
     */
    public function close($outTradeNo){
        $params = [
            'appid'=>$this->conf['app_id'],
            'mch_id'=>$this->conf['payment']['mch_id'],
            'out_trade_no'=>$outTradeNo,
            'nonce_str'=>Yii::$app->security->generateRandomString(32),
        ];

        $params['sign'] = Util::makeSign($params,$this->conf['payment']['key']);

        $response = $this->post(self::API_CLOSE_URL,$params)
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
     * 退款操作
     *
     * @param $outTradeNo string 商户订单号 / 微信订单号，取决于$isTransactionId的值
     * @param $isTransactionId boolean 是否为微信订单号
     * @param $outRefundNo string 退款单号
     * @param $totalFee integer 订单金额（分）
     * @param $refundFee integer 退款金额（分）
     * @param $extra array 额外参数
     * @throws Exception
     * @return array
     */
    public function refund($outTradeNo,$isTransactionId = false,$outRefundNo,$totalFee,$refundFee,$extra = []){
        $params = [
            'appid'=>$this->conf['app_id'],
            'mch_id'=>$this->conf['payment']['mch_id'],
            'nonce_str'=>Yii::$app->security->generateRandomString(32),
            'out_refund_no'=>$outRefundNo,
            'total_fee'=>$totalFee,
            'refund_fee'=>$refundFee,
        ];

        if($isTransactionId == true){
            $params['transaction_id'] = $outTradeNo;
        }else{
            $params['out_trade_no'] = $outTradeNo;
        }

        if($extra){
            $params = array_merge($params,$extra);
        }

        $params['sign'] = Util::makeSign($params,$this->conf['payment']['key']);

        $options = [
            CURLOPT_SSLCERTTYPE=>'PEM',
            CURLOPT_SSLCERT=>$this->conf['payment']['cert_path'],
            CURLOPT_SSLKEYTYPE=>'PEM',
            CURLOPT_SSLKEY=>$this->conf['payment']['key_path'],
        ];

        $response = $this->post(self::API_REFUND_URL,$params,[],$options)->setFormat(Client::FORMAT_XML)->send();

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
     * 查询退款订单情况
     * @param $number string 商户订单号 / 微信订单号 / 退款订单号 / 微信退款订单号
     * @param string $type 编号类型
     * @return mixed
     * @throws Exception
     */
    public function queryRefund($number,$type = self::QUERY_REFUND_OUT_TRADE_NO){
        $params = [
            'appid'=>$this->conf['app_id'],
            'mch_id'=>$this->conf['payment']['mch_id'],
            'nonce_str'=>Yii::$app->security->generateRandomString(32),
        ];

        switch ($type){
            case self::QUERY_REFUND_OUT_TRADE_NO:
                $params['out_trade_no'] = $number;
                break;
            case self::QUERY_REFUND_TRANSACTION_ID:
                $params['transaction_id'] = $number;
                break;
            case self::QUERY_REFUND_OUT_REFUND_NO:
                $params['out_refund_no'] = $number;
                break;
            case self::QUERY_REFUND_REFUND_ID:
                $params['refund_id'] = $number;
                break;
        }


        $params['sign'] = Util::makeSign($params,$this->conf['payment']['key']);

        $response = $this->post(self::API_REFUND_QUERY_URL,$params)->setFormat(Client::FORMAT_XML)->send();

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
     * 退款结果通知
     * @param callable $callback
     * @return string
     */
    public function handleRefundNotify(callable $callback)
    {
        $notify = (new RefundNotify(['merchant'=>$this->conf['payment']]))->getData();
        $isSuccess = $notify['return_code'] === 'SUCCESS';

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
        return XML::build($response);
    }

    /**
     * 下载对账单
     * 该函数返回数据内容，如果你下载文件，可以在bill的结果return之前设置content-type，比如下面的代码
     * ```php
     * $response = $pay->bill('20180202',Pay::TYPE_BILL_ALL);
     * header('Content-Disposition: attachment; filename="20180202.csv"');
     * return $response;
     * ```
     *
     * @param $date string 时间
     * @param $type string 账单类型
     * @author abei<abei@nai8.me>
     * @link https://nai8.me
     * @throws Exception
     * @return string
     */
    public function bill($date, $type = self::TYPE_BILL_ALL){
        $params = [
            'appid'=>$this->conf['app_id'],
            'mch_id'=>$this->conf['payment']['mch_id'],
            'nonce_str'=>Yii::$app->security->generateRandomString(32),
            'bill_date'=>$date,
            'bill_type'=>$type
        ];

        $params['sign'] = Util::makeSign($params,$this->conf['payment']['key']);

        $response = $this->post(self::API_DOWNLOAD_BILL_URL,$params)->setFormat(Client::FORMAT_XML)->send();
        if($response->isOk == false) {
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        return $response->getContent();
    }

    /**
     * 将url转化为短地址
     * @param $longUrl string 长url地址
     * @throws Exception
     * @return string
     */
    public function url2short($longUrl){
        $params = [
            'appid'=>$this->conf['app_id'],
            'mch_id'=>$this->conf['payment']['mch_id'],
            'nonce_str'=>Yii::$app->security->generateRandomString(32),
            'long_url'=>$longUrl
        ];

        $params['sign'] = Util::makeSign($params,$this->conf['payment']['key']);

        $response = $this->post(self::API_SHORT_URL_URL,$params)->setFormat(Client::FORMAT_XML)->send();
        if($response->isOk == false) {
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

        return $result['short_url'];
    }
}