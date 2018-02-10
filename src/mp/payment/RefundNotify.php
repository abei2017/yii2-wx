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
use yii\base\Component;
use abei2017\wx\helpers\Xml;

/**
 * RefundNotify
 * @package abei2017\wx\mp\payment
 * @author abei<abei@nai8.me>
 * @link https://nai8.me/yii2wx
 */
class RefundNotify extends Component {


    public $merchant;

    private $data;

    public function getData(){
        if($this->data){
            return $this->data;
        }

        $data = Xml::parse(file_get_contents('php://input'));

        $data['req_info'] = $this->decodeInfo($data['req_info']);
        return $this->data = $data;
    }

    protected function decodeInfo($data){
        $decode64 = base64_decode($data, true);
        $key = md5($this->merchant['key']);
        $decrypted = openssl_decrypt($decode64, 'aes-256-ecb', $key, OPENSSL_RAW_DATA);
        return XML::parse($decrypted);
    }
}