<?php

/*
 * This file is part of the abei2017/yii2-wx
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx\mp\encryptor;

use Yii;
use abei2017\wx\core\Driver;
use abei2017\wx\core\Exception;
use abei2017\wx\helpers\Xml;

/**
 * Encryptor
 * 加密解密
 *
 * @author abei<abei@nai8.me>
 * @link https://nai8.me/yii2wx
 * @package abei2017\wx\mp\encryptor
 */
class Encryptor extends Driver {

    protected $blockSize = 32;

    public function encryptMsg($xml, $nonce, $timestamp){
        $encrypt = $this->encrypt($xml,$this->conf['app_id']);
        $sign = $this->getSHA1($this->conf['token'], $timestamp, $nonce, $encrypt);

        $response = [
            'Encrypt'=>$encrypt,
            'MsgSignature'=>$sign,
            'TimeStamp'=>$timestamp,
            'Nonce'=>$nonce
        ];

        return Xml::build($response);
    }

    public function decryptMsg($msgSignature, $nonce, $timestamp, $postXML){
        $arr = Xml::parse($postXML);
        $encrypted = $arr['Encrypt'];
        $sign = $this->getSHA1($this->conf['token'], $timestamp, $nonce, $encrypted);

        if($sign !== $msgSignature){
            throw new Exception("无效的签名");
        }

        return Xml::parse($this->decrypt($encrypted));
    }

    public function getSHA1(){
        $arr = func_get_args();
        sort($arr,SORT_STRING);

        return sha1(implode($arr));
    }


    private function encrypt($text, $appId)
    {
        try {
            $key = $this->getAESKey();
            $random = $this->getRandomStr();
            $text = $this->encode($random.pack('N', strlen($text)).$text.$appId);
            $iv = substr($key, 0, 16);
            $encrypted = openssl_encrypt($text, 'aes-256-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
            return base64_encode($encrypted);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function decrypt($encrypted){
        $key = $this->getAESKey();
        $ciphertext = base64_decode($encrypted,true);

        $iv = substr($key,0,16);

        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);

        $result = $this->decode($decrypted);
        if (strlen($result) < 16) {
            return "";
        }

        $content = substr($result,16,strlen($result));
        $listLen = unpack('N',substr($content,0,4));
        $xmlLen = $listLen[1];
        $xml = substr($content,4,$xmlLen);

        $dataSet = json_decode($xml, true);
        if ($dataSet && (JSON_ERROR_NONE === json_last_error())) {
            $xml = Xml::build($dataSet);
        }
        return $xml;
    }

    protected function getAESKey(){
        if(empty($this->conf['encodingAESKey'])){
            throw new Exception("aes_key 不能为空");
        }

        if(strlen($this->conf['encodingAESKey']) !== 43){
            throw new Exception("aes_key长度必须为43位");
        }

        return base64_decode($this->conf['encodingAESKey']."=",true);
    }

    /**
     * 对内容进行解码
     * @param $decrypted
     * @return bool|string
     */
    public function decode($decrypted){
        $pad = ord(substr($decrypted,-1));
        if($pad < 1 || $pad > $this->blockSize){
            $pad = 0;
        }

        return substr($decrypted,0,(strlen($decrypted) - $pad));
    }

    /**
     * 对内容进行编码
     * @param $text
     * @return string
     */
    public function encode($text)
    {
        $padAmount = $this->blockSize - (strlen($text) % $this->blockSize);
        $padAmount = 0 !== $padAmount ? $padAmount : $this->blockSize;
        $padChr = chr($padAmount);
        $tmp = '';
        for ($index = 0; $index < $padAmount; ++$index) {
            $tmp .= $padChr;
        }
        return $text.$tmp;
    }

    /**
     * 生成16位的随机字符串
     * @return bool|string
     */
    private function getRandomStr(){
        return Yii::$app->security->generateRandomString(16);
    }
}