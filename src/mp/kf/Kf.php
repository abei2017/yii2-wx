<?php

namespace abei2017\wx\mp\kf;

use abei2017\wx\core\Driver;
use abei2017\wx\core\AccessToken;
use yii\httpclient\Client;
use abei2017\wx\core\Exception;

/**
 * 客服助手
 *
 * @author abei<abei@nai8.me>
 * @package abei2017\wx\mp\kf
 */
class Kf extends Driver {

    /**
     * 增加客服接口[新]
     */
    const API_ADD_KF_URL = 'https://api.weixin.qq.com/customservice/kfaccount/add';

    /**
     * 获取所有客服列表
     */
    const API_LIST_KF_URL = 'https://api.weixin.qq.com/cgi-bin/customservice/getkflist';

    /**
     * 邀请绑定客服账号
     */
    const API_INVITE_KF_URL = 'https://api.weixin.qq.com/customservice/kfaccount/inviteworker';

    /**
     * 删除客服账号接口地址
     */
    const API_DELETE_KF_URL = 'https://api.weixin.qq.com/customservice/kfaccount/del';

    /**
     * 更新客服信息接口
     */
    const API_UPDATE_KF_URL = 'https://api.weixin.qq.com/customservice/kfaccount/update';

    /**
     * 上传客服头像接口
     */
    const API_UPLOAD_AVATAR_URL = 'https://api.weixin.qq.com/customservice/kfaccount/uploadheadimg';

    /**
     * 关闭会话接口
     */
    const API_CLOSE_SESSION_URL = 'https://api.weixin.qq.com/customservice/kfsession/close';

    /**
     * 创建会话接口
     */
    const API_CREATE_SESSION_URL = 'https://api.weixin.qq.com/customservice/kfsession/create';

    /**
     * 未接入的会话
     */
    const API_WAIT_SESSION_URL = 'https://api.weixin.qq.com/customservice/kfsession/getwaitcase';

    /**
     * 获取客服会话列表
     */
    const API_KF_SESSION_URL = 'https://api.weixin.qq.com/customservice/kfsession/getsessionlist';

    /**
     * 获取客户会话状态
     */
    const API_CUSTOM_SESSION_URL = 'https://api.weixin.qq.com/customservice/kfsession/getsession';

    /**
     * 获取聊天记录
     */
    const API_MSG_LIST_URL = 'https://api.weixin.qq.com/customservice/msgrecord/getmsglist';

    private $accessToken;

    public function init(){
        parent::init();
        $this->accessToken = (new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken();
    }

    /**
     * 新增客服账号
     * @param string $account 完整客服帐号，格式为：帐号前缀@公众号微信号
     * @return boolean
     * @throws Exception
     */
    public function add($account,$nickname){
        $this->httpClient->formatters = ['uncodeJson'=>'abei2017\wx\helpers\JsonFormatter'];
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_ADD_KF_URL."?access_token={$this->accessToken}")
            ->setFormat('uncodeJson')
            ->setMethod('post')
            ->setData([
                'kf_account'=>$account,
                'nickname'=>$nickname,
            ])
            ->send();

        if($response->isOk == false){
            throw new Exception('网络问题，没有得到服务器响应。');
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode']) && $data['errcode'] != 0){
            throw new Exception($data['errmsg']);
        }

        return true;
    }

    /**
     * 获取客服列表
     * @return mixed
     * @throws Exception
     */
    public function ls(){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_LIST_KF_URL."?access_token={$this->accessToken}")
            ->setFormat(Client::FORMAT_JSON)
            ->setMethod('get')
            ->send();

        if($response->isOk == false){
            throw new Exception('网络问题，没有得到服务器响应。');
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode']) && $data['errcode'] != 0){
            throw new Exception($data['errmsg']);
        }

        return $data['kf_list'];
    }

    /**
     * 邀请绑定客服帐号
     * @param $account string 客服账号
     * @param $wxName string 微信账号
     */
    public function invite($account,$wxName){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_INVITE_KF_URL."?access_token={$this->accessToken}")
            ->setFormat(Client::FORMAT_JSON)
            ->setMethod('post')
            ->setData([
                'kf_account'=>$account,
                'invite_wx'=>$wxName,
            ])
            ->send();

        if($response->isOk == false){
            throw new Exception('网络问题，没有得到服务器响应。');
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode']) && $data['errcode'] != 0){
            throw new Exception($data['errmsg']);
        }

        return true;
    }

    /**
     * 删除一个客服
     * @param $account
     */
    public function delete($account){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_DELETE_KF_URL."?access_token={$this->accessToken}&kf_account={$account}")
            ->setFormat(Client::FORMAT_JSON)
            ->setMethod('get')
            ->send();

        if($response->isOk == false){
            throw new Exception('网络问题，没有得到服务器响应。');
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode']) && $data['errcode'] != 0){
            throw new Exception($data['errmsg']);
        }

        return true;
    }

    /**
     * 更新客服信息
     */
    public function update($account,$nickname){
        $this->httpClient->formatters = ['uncodeJson'=>'abei2017\wx\helpers\JsonFormatter'];
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_UPDATE_KF_URL."?access_token={$this->accessToken}")
            ->setFormat('uncodeJson')
            ->setMethod('post')
            ->setData([
                'kf_account'=>$account,
                'nickname'=>$nickname,
            ])
            ->send();

        if($response->isOk == false){
            throw new Exception('网络问题，没有得到服务器响应。');
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode']) && $data['errcode'] != 0){
            throw new Exception($data['errmsg']);
        }

        return true;
    }

    /**
     * 设置头像
     * @param $account string 客服账户名
     * @param $avatar string 头像物理路径名
     * @return boolean
     * @throws Exception
     */
    public function avatar($account,$avatar){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_UPLOAD_AVATAR_URL."?access_token={$this->accessToken}&kf_account=".$account)
            ->setMethod('post')
            ->addFile('media', $avatar)
            ->send();

        if($response->isOk == false){
            throw new Exception('网络问题，没有得到服务器响应。');
        }

        $response->setFormat(Client::FORMAT_JSON);
        $result = $response->getData();
        if(isset($result['errcode']) && $result['errcode'] != 0){
            throw new Exception($result['errmsg']);
        }

        return true;
    }

    /**
     * 关闭会话
     */
    public function closeSession($account,$openId){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_CLOSE_SESSION_URL."?access_token={$this->accessToken}")
            ->setFormat(Client::FORMAT_JSON)
            ->setMethod('post')
            ->setData([
                'kf_account'=>$account,
                'openid'=>$openId
            ])
            ->send();

        if($response->isOk == false){
            throw new Exception('网络问题，没有得到服务器响应。');
        }

        $response->setFormat(Client::FORMAT_JSON);
        $result = $response->getData();
        if(isset($result['errcode']) && $result['errcode'] != 0){
            throw new Exception($result['errmsg']);
        }

        return true;
    }

    public function createSession($account,$openId){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_CREATE_SESSION_URL."?access_token={$this->accessToken}")
            ->setFormat(Client::FORMAT_JSON)
            ->setMethod('post')
            ->setData([
                'kf_account'=>$account,
                'openid'=>$openId
            ])
            ->send();

        if($response->isOk == false){
            throw new Exception('网络问题，没有得到服务器响应。');
        }

        $response->setFormat(Client::FORMAT_JSON);
        $result = $response->getData();
        if(isset($result['errcode']) && $result['errcode'] != 0){
            throw new Exception($result['errmsg']);
        }

        return true;
    }

    public function waitingSessions(){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_WAIT_SESSION_URL."?access_token={$this->accessToken}")
            ->setMethod('get')
            ->send();

        $response->setFormat(Client::FORMAT_JSON);
        $result = $response->getData();

        return $result;
    }

    /**
     * 获得一个客服当前所有的
     * @param $account
     * @return array
     * @throws Exception
     */
    public function kfSessions($account){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_KF_SESSION_URL."?access_token={$this->accessToken}&kf_account={$account}")
            ->setMethod('get')
            ->setFormat(Client::FORMAT_JSON)
            ->send();

        $response->setFormat(Client::FORMAT_JSON);
        $result = $response->getData();

        if($response->isOk == false){
            throw new Exception('网络问题，没有得到服务器响应。');
        }
        if(isset($result['errcode']) && $result['errcode'] != 0){
            throw new Exception($result['errmsg']);
        }

        return isset($result['sessionlist']) ? $result['sessionlist'] : [];
    }

    public function customSession($openId){
        $response = $this->httpClient->createRequest()
            ->setUrl(self::API_CUSTOM_SESSION_URL."?access_token={$this->accessToken}&openid={$openId}")
            ->setMethod('get')
            ->setFormat(Client::FORMAT_JSON)
            ->send();

        $response->setFormat(Client::FORMAT_JSON);
        $result = $response->getData();

        if($response->isOk == false){
            throw new Exception('网络问题，没有得到服务器响应。');
        }
        if(isset($result['errcode']) && $result['errcode'] != 0){
            throw new Exception($result['errmsg']);
        }

        return $result;
    }

    /**
     * 获取聊天记录
     *
     * @param $start integer 开始时间
     * @param $end integer 结束时间（开始时间和结束时间不能超过24小时）
     * @param $msgId
     * @param $number
     * @throws Exception
     * @return array
     */
    public function msgList($start,$end,$msgId = 1,$number = 10000){

        $response = $this->httpClient->createRequest()
            ->setMethod('post')
            ->setUrl(self::API_MSG_LIST_URL."?access_token={$this->accessToken}")
            ->setFormat(Client::FORMAT_JSON)
            ->setData(['starttime'=>$start, 'endtime'=>$end, 'msgid'=>$msgId, 'number'=>$number])
            ->send();

        if($response->isOk == false){
            throw new Exception('网络问题，没有得到服务器响应。');
        }

        $response->setFormat(Client::FORMAT_JSON);
        $result = $response->getData();

        if(isset($result['errcode']) && $result['errcode'] != 0){
            throw new Exception($result['errmsg']);
        }

        return $result;
    }
}