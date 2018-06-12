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

use yii\base\Component;

/**
 * 工具类库
 *
 * @author abei<abei@nai8.me>
 * @link https://nai8.me/yii2wx
 * @package abei2017\wx\helpers
 */
class Util extends Component {

    /**
     * 生成支付签名前相关参数到url的转化
     *
     * @param $params array 相关参数
     * @return string
     */
    static public function paramsToUrl($params){
        $buff = "";
        foreach($params as $k=>$v){
            if($k != "sign" && $v != "" && is_array($v) == false){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff,"&");
        return $buff;
    }

    static public function makeSign($params,$key){
        ksort($params);
        $str = self::paramsToUrl($params);
        $str .= "&key=".$key;
        return strtoupper(md5($str));
    }

}