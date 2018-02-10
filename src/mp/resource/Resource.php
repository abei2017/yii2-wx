<?php
/*
 * This file is part of the abei2017/yii2-wx
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx\mp\resource;

use abei2017\wx\core\Driver;
use abei2017\wx\core\AccessToken;
use abei2017\wx\core\Exception;
use yii\helpers\Json;
use yii\httpclient\Client;

/**
 * 素材助手
 * @author abei<abei@nai8.me>
 * @link https://nai8.me/yii2wx
 * @package abei2017\wx\mp\resource
 */
class Resource extends Driver {

    private $accessToken;

    //  临时素材上传接口
    const API_MEDIA_UPLOAD_URL = 'https://api.weixin.qq.com/cgi-bin/media/upload';
    //  获取临时素材接口
    const API_MEDIA_GET_URL = 'https://api.weixin.qq.com/cgi-bin/media/get';
    //  上传永久素材
    const API_FOREVER_MEDIA_UPLOAD_URL = 'https://api.weixin.qq.com/cgi-bin/material/add_material';
    //  获得一个永久素材
    const API_FOREVER_MEDIA_URL = 'https://api.weixin.qq.com/cgi-bin/material/get_material';
    //  删除一个永久素材
    const API_FOREVER_MEDIA_DELETE_URL = 'https://api.weixin.qq.com/cgi-bin/material/del_material';
    //  获得素材总数统计
    const API_FOREVER_MEDIA_TOTAL_URL = 'https://api.weixin.qq.com/cgi-bin/material/get_materialcount';
    //  获得素材列表
    const API_FOREVER_MEDIA_LIST_URL = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material';
    //  添加图文素材
    const API_NEWS_ADD_URL = 'https://api.weixin.qq.com/cgi-bin/material/add_news';
    //  上传图文消息中的图片
    const API_MEDIA_UPLOADIMG_URL = 'https://api.weixin.qq.com/cgi-bin/media/uploadimg';
    //  更新图文消息
    const API_UPDATE_NEWS_URL = 'https://api.weixin.qq.com/cgi-bin/material/update_news';

    public function init(){
        parent::init();
        $this->accessToken = (new AccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken();
    }

    /**
     * 新增一个临时素材
     * @param $file string 文件路径
     * @param $type string 素材类型
     * @throws Exception
     * @return string
     */
    public function addTempMedia($file,$type = 'image'){
        $response = $this->post(self::API_MEDIA_UPLOAD_URL."?access_token={$this->accessToken}&type={$type}")
            ->addFile('media', $file)->send();

        $response->setFormat(Client::FORMAT_JSON);
        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $result = $response->getData();
        if(isset($result['errcode']) && $result['errcode'] != 0){
            throw new Exception($result['errmsg'],$result['errcode']);
        }

        return $result['media_id'];
    }

    /**
     * 获取一个mediaId对应的二进制流内容
     * @param $mediaId string
     * @param $savePath string|boolean 保存路径
     * @throws Exception
     */
    public function getMedia($mediaId, $savePath = false){
        $response = $this->get(self::API_MEDIA_GET_URL,['access_token'=>$this->accessToken,'media_id'=>$mediaId])->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $contentType = $response->getHeaders()->get('content-type');
        if($contentType == 'applicatioin/json'){
            //  报错或返回视频的url
            $data = $response->getData();
            if(isset($data['errcode'])){
                throw new Exception($data['errmsg']);
            }

            if(isset($data['video_url'])){
                return $data['video_url'];
            }
        }else if ($contentType == 'image/jpeg'){
            //  图片类型
            header('Content-type:'.$contentType);
            $stream = $response->getContent();
            return $stream;
        }else if(in_array($contentType,['audio/amr','voice/speex'])){
            //  音频类型
            $stream = $response->getContent();
            return $stream;
        }
    }

    public function addForeverMedia($file,$type = 'image',$videoForm=[]){
        $request = $this->post(self::API_FOREVER_MEDIA_UPLOAD_URL."?access_token={$this->accessToken}&type={$type}")
            ->addFile('media', $file);

        if($type == 'video'){
            //  添加视频描述
            $request->addData(['description'=>Json::encode($videoForm)]);
        }

        $response = $request->send();
        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_JSON);
        $result = $response->getData();
        if(isset($result['errcode'])){
            throw new Exception($result['errmsg']);
        }

        if($type == 'image'){
            return $result;
        }else{
            return $result['media_id'];
        }
    }

    /**
     * 获得一个永久素材
     * @param $mediaId
     * @return mixed
     * @throws Exception
     */
    public function getForeverMedia($mediaId){
        $response = $this->post(self::API_FOREVER_MEDIA_UPLOAD_URL."?access_token={$this->accessToken}",['media_id'=>$mediaId])
            ->setFormat(Client::FORMAT_JSON)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $contentType = $response->getHeaders()->get('content-type');
        if($contentType == 'applicatioin/json'){
            //  报错或返回视频的url
            $data = $response->getData();
            if(isset($data['errcode'])){
                throw new Exception($data['errmsg']);
            }

            return $data;
        }else if ($contentType == 'image/jpeg'){
            //  图片类型
            header('Content-type:'.$contentType);
            $stream = $response->getContent();
            return $stream;
        }else if(in_array($contentType,['audio/amr','voice/speex'])){
            //  音频类型
            $stream = $response->getContent();
            return $stream;
        }
    }

    /**
     * 删除一个永久素材
     */
    public function deleteForeverMedia($mediaId){
        $response = $this->post(self::API_FOREVER_MEDIA_DELETE_URL."?access_token={$this->accessToken}",['media_id'=>$mediaId])
            ->setFormat(Client::FORMAT_JSON)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode']) && $data['errcode'] <> 0){
            throw new Exception($data['errmsg'],$data['errcode']);
        }

        return true;
    }

    /**
     * 获得永久素材统计数据
     * @return mixed
     * @throws Exception
     */
    public function foreverMediaTotal(){
        $response = $this->get(self::API_FOREVER_MEDIA_TOTAL_URL."?access_token={$this->accessToken}")->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode']) && $data['errcode'] <> 0){
            throw new Exception($data['errmsg'],$data['errcode']);
        }

        return $data;
    }

    /**
     * 获得永久素材列表
     *
     * @param string $type
     * @param int $offset
     * @param int $count
     * @return mixed
     * @throws Exception
     */
    public function foreverMediaList($type = 'image', $offset = 0, $count = 20){
        $response = $this->post(self::API_FOREVER_MEDIA_LIST_URL."?access_token={$this->accessToken}",[
            'type'=>$type,
            'offset'=>$offset,
            'count'=>$count
        ])->setFormat(Client::FORMAT_JSON)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if(isset($data['errcode']) && $data['errcode'] <> 0){
            throw new Exception($data['errmsg'],$data['errcode']);
        }

        return $data;
    }

    /**
     * 添加一个图文
     * @param $articles array 图文数组，每个一图文。
     * @return mixed
     * @throws Exception
     */
    public function addNews($articles = []){
        $response = $this->post(self::API_NEWS_ADD_URL."?access_token={$this->accessToken}",['articles'=>$articles])->setFormat(Client::FORMAT_JSON)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        return $data['media_id'];

    }

    public function uploadImgInNews($file){
        $response = $this->post(self::API_MEDIA_UPLOADIMG_URL."?access_token={$this->accessToken}")
            ->addFile('media', $file)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        return $data['url'];
    }

    public function updateNews($mediaId,$index,$article){
        $response = $this->post(self::API_UPDATE_NEWS_URL."?access_token={$this->accessToken}",[
            'media_id'=>$mediaId, 'index'=>$index, 'articles'=>$article
        ])->setFormat(Client::FORMAT_JSON)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();

        if($data['errcode'] == 0){
            return true;
        }else{
            throw new Exception($data['errmsg']);
        }
    }
}