<?php

/*
 * This file is part of the abei2017/yii2-wx.
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx\helpers;

/**
 * 自定义的json数据formatter
 * 该formatter主要是在数据进行json_encode时对其中的汉字内容不进行编码
 *
 * @author abei<abei@nai8.me>
 * @link https://nai8.me/lang-7.html
 * @package abei2017\wx\helpers
 */
class JsonFormatter extends \yii\httpclient\JsonFormatter {

    public $encodeOptions = 256;
}