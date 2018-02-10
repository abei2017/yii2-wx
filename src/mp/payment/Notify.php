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

use yii\base\Component;
use abei2017\wx\helpers\Xml;
use abei2017\wx\helpers\Util;
use abei2017\wx\core\Exception;

/**
 * Notify
 * 微信支付通知类
 *
 * @author abei<abei@nai8.me>
 * @link http://nai8.me/yii2wx
 * @package abei2017\wx\mp\payment
 */
class Notify extends Component {

    /**
     * 收到的通知（数组形式）
     * @var
     */
    protected $notify;

    public $merchant;

    protected $data = false;

    public function getData(){
        if($this->data){
            return $this->data;
        }

        return $this->data = Xml::parse(file_get_contents('php://input'));
    }

    public function checkSign(){
        if($this->data == false){
            $this->getData();
        }

        $sign = Util::makeSign($this->data,$this->merchant['key']);
        if($sign != $this->data['sign']){
            throw new Exception("签名错误！");
        }

        return true;
    }
}