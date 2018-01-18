<?php

namespace abei2017\wx\mp\server;

use abei2017\wx\helpers\Xml;
use Yii;
use abei2017\wx\core\Driver;
use yii\base\Exception;
use abei2017\wx\mp\message\Text;

/**
 * 服务器类
 * @package abei2017\wx\server
 * @author abei<abei@nai8.me>
 */

class Server extends Driver {

    const SUCCESS_EMPTY_RESPONSE = 'success';

    const ALL_MSG = 1049598;

    protected $messageHandler;

    protected $messageFilter;

    protected $encodingAESKey;

    /**
     * 发送响应
     */
    public function serve(){
        $this->validate();

        if($echoStr = Yii::$app->request->get('echostr')){
            Yii::$app->response->content = $echoStr;
            Yii::$app->response->send();
            return true;
        }

        //  back
        $result = $this->handleRequest();
        $response = $this->buildResponse($result['to'], $result['from'], $result['response']);

        Yii::$app->response->content = $response;
        Yii::$app->response->send();
    }

    /**
     * 验证签名
     * @author abei<abei@nai8.me>
     */
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

    /**
     * 生成签名
     * @param $params array token & timestamp & nonce
     * @return string
     */
    protected function signature($params){
        sort($params,SORT_STRING);
        return sha1(implode($params));
    }


    protected function handleRequest(){
        $message = $this->getMessage();
        $response = $this->handleMessage($message);

        return [
            'to'=>$message['FromUserName'],
            'from'=>$message['ToUserName'],
            'response'=>$response
        ];
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

    public function setMessageHandler($callback,$option = self::ALL_MSG){
        if(!is_callable($callback)){
            throw new Exception('error');
        }

        $this->messageHandler = $callback;
        $this->messageFilter = $option;

        return $this;
    }

    protected function parseMessageInRequest($content = null){
        $message = Xml::parse($content);

        return $message;
    }

    protected function buildResponse($to,$from,$message){
        if (empty($message) || self::SUCCESS_EMPTY_RESPONSE === $message) {
            return self::SUCCESS_EMPTY_RESPONSE;
        }

        //  文本或数字
        if (is_string($message) || is_numeric($message)) {
            $message = new Text(['props' =>['Content'=>$message]]);
        }

        $response = $this->buildReply($to, $from, $message);

        return $response;
    }

    protected function buildReply($to, $from, $message){
        $base = [
            'ToUserName' => $to,
            'FromUserName' => $from,
            'CreateTime' => time(),
            'MsgType' => $message->type,
        ];

        return Xml::build(array_merge($base, $message->props));
    }
}