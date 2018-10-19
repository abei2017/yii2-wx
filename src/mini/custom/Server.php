<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2018/10/19
 * Time: 5:45 PM
 */

namespace abei2017\wx\mini\custom;

use abei2017\wx\helpers\Xml;
use Yii;
use abei2017\wx\core\Driver;
use yii\base\Exception;
use abei2017\wx\mini\encryptor\Encryptor;

class Server extends Driver {

    const SUCCESS_EMPTY_RESPONSE = 'success';

    protected $messageHandler;
    protected $messageFilter;

    const ALL_MSG = 1049598;

    protected $encryptor;

    protected $encodingAESKey;

    public function init() {
        parent::init();
        $this->encryptor = (new Encryptor(['conf'=>$this->conf,'httpClient'=>$this->httpClient]));
    }

    public function serve(){
        $this->validate();
        if($echoStr = Yii::$app->request->get('echostr')){
            Yii::$app->response->content = $echoStr;
            Yii::$app->response->send();
            return true;
        }

        $result = $this->handleRequest();

        $response = $this->buildResponse($result['to'], $result['response']);

        Yii::$app->response->content = $response;
        Yii::$app->response->send();
    }

    protected function validate(){
        $token = $this->conf['token'];

        $params = [
            $token,
            Yii::$app->request->get('timestamp'),
            Yii::$app->request->get('nonce'),
        ];

        if (Yii::$app->request->get('signature') !== $this->signature($params)) {
            throw new Exception('无效的请求签名.', 400);
        }
    }

    protected function signature($params){
        sort($params,SORT_STRING);
        return sha1(implode($params));
    }


    protected function getMessage(){

        $message = $this->parseMessageInRequest(file_get_contents('php://input'));

        return $message;
    }

    protected function handleMessage($message){
        $handler = $this->messageHandler;
        if(!is_callable($handler)){
            return false;
        }

        $type = $message['MsgType'];
        $response = null;

        if($this->messageFilter && $type){
            $response = call_user_func_array($handler, [$message]);
        }

        return $response;
    }

    protected function handleRequest(){
        $message = $this->getMessage();
        $response = $this->handleMessage($message);

        return [
            'to'=>$message['FromUserName'],
            'response'=>$response
        ];
    }

    protected function parseMessageInRequest($content = null){
        if($this->conf['safeMode'] > 0){
            $message = $this->encryptor->decryptMsg(
                Yii::$app->request->get('msg_signature'),
                Yii::$app->request->get('nonce'),
                Yii::$app->request->get('timestamp'),
                $content
            );
        }else{
            $message = Xml::parse($content);
        }

        return $message;
    }

    public function setMessageHandler($callback,$option = self::ALL_MSG){
        if(!is_callable($callback)){
            throw new Exception('error');
        }

        $this->messageHandler = $callback;
        $this->messageFilter = $option;

        return $this;
    }

    public function buildResponse($to,$message){
        if (empty($message) || self::SUCCESS_EMPTY_RESPONSE === $message) {
            return self::SUCCESS_EMPTY_RESPONSE;
        }

        $customer = new Customer(['conf'=>$this->conf,'httpClient'=>$this->httpClient]);

        if (is_string($message) || is_numeric($message)) {
            $customer->send($to,'text',$message);
        }else{
            $customer->send($to,$message['msgType'],$message['data']);
        }

        return "success";
    }
}