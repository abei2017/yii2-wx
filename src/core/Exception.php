<?php
/*
 * This file is part of the abei2017/yii2-wx.
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx\core;

/**
 * Exception
 * yii2-wx专属异常类
 * @author abei<abei@nai8.me>
 */
class Exception extends \yii\base\Exception {

    public function getName(){
        return '微信SDK（abei2017/yii2-wx）';
    }

}
