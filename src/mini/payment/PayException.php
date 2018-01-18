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
 * Class PayException
 * @package abei2017\mini\pay
 * @author abei<abei@nai8.me>
 * @link http://nai8.me
 * @version 1.0
 */

class PayException extends \yii\base\Exception {

    public function getName(){
        return 'Mini Program Exception';
    }


}