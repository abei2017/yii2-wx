<?php

namespace abei2017\wx\mp\payment;

class PayException extends \yii\base\Exception {

    public function getName(){
        return 'MP PAY Program Exception';
    }
}