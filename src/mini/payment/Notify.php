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

/**
 * Notify API.
 * @package abei2017\mini\pay
 * @author abei<abei@nai8.me>
 */
class Notify {

    /**
     * @var $notify
     */
    protected $notify;

    /**
     * @var
     */
    protected $merchant;

    /**
     * @var boolean | array
     */
    protected $data = false;

    public function __construct($merchant){
        $this->merchant = $merchant;
    }

    public function getData(){
        if($this->data){
            return $this->data;
        }

        $xml = @$GLOBALS['HTTP_RAW_POST_DATA'];
        $xmlArray = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        return $this->data = $xmlArray;
    }

    /**
     * 检测签名
     * @author abei<abei@nai8.me>
     */
    public function checkSign(){
        if($this->data == false){
            $this->getData();
        }

        $sign = $this->makeSign();
        if($this->GetSign() == $sign){
            return true;
        }
        throw new PayException("签名错误！");
    }

    public function GetSign(){
        return $this->data['sign'];
    }

    protected function makeSign(){
        $data = $this->data;
        unset($data['sign']);

        $params = [];
        foreach ($data as $k => $v)
        {
            $params[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($params);
        $String = $this->toUrlParams($params);
        $String = $String."&key=".$this->merchant['key'];
        $String = md5($String);
        $result_ = strtoupper($String);
        return $result_;
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
}