<?php

namespace abei2017\wx\mp\payment;

use yii\base\Component;
use abei2017\wx\helpers\Xml;

class Notify extends Component {

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

        $sign = $this->makeSign();
        if($sign != $this->data['sign']){
            throw new PayException("签名错误！");
        }

        return true;
    }

    protected function makeSign(){
        $data = $this->data;
        unset($data['sign']);

        $params = [];
        foreach($data as $k=>$v){
            $params[$k] = $v;
        }

        ksort($params);
        $str = $this->toUrlParams($params);
        $str .= "&key=".$this->merchant['key'];
        $str = md5($str);

        return strtoupper($str);
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