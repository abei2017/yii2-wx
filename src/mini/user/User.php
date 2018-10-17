<?php
/*
 * This file is part of the abei2017/yii2-wx
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx\mini\user;

use abei2017\wx\core\Driver;
use yii\httpclient\Client;
use abei2017\wx\core\Exception;

/**
 * User
 * @author abei<abei@nai8.me>
 * @link https://nai8.me/study/yii2wx.html
 * @package abei2017\wx\mini\user
 */
class User extends Driver {

    const API_CODE_TO_SESSION_URL = "https://api.weixin.qq.com/sns/jscode2session";

    public function codeToSession($code){
        $response = $this->get(self::API_CODE_TO_SESSION_URL."?appid={$this->conf['app_id']}&secret={$this->conf['secret']}&js_code={$code}&grant_type=authorization_code")->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $data = $response->getData();
        return $data;
    }

    /**
     * 解密信息
     * 主要用于wx.getUserInfo时对加密数据的解密。
     *
     * @param $sessionKey
     * @param $iv
     * @param $encryptedData
     * @return array
     * @since 1.3.1
     */
    public function decryptData($sessionKey,$iv,$encryptedData){
        $aesKey = base64_decode($sessionKey);
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj = json_decode( $result,true );
        return $dataObj;
    }
}