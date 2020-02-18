<?php
/**
 * Created by PhpStorm.
 * User: leeyifiei
 * Date: 2019/9/27
 * Time: 9:01 AM
 */

namespace abei2017\wx\mini\seccheck;


use abei2017\wx\core\AccessToken;
use abei2017\wx\core\Driver;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use Yii;

class ImgCheck extends Driver
{
    const API_SEND_CHECK = 'https://api.weixin.qq.com/wxa/img_sec_check?access_token=';

    private $accessToken = null;

    private $_retries = 0;

    public function init()
    {
        parent::init();
        $this->accessToken = (new AccessToken(['conf' => $this->conf, 'httpClient' => $this->httpClient]))->getToken();
    }


    public function check($image, $isRemote = true)
    {
        $localPath = $image;
        if ($isRemote) {
            $response = $this->get($image)->send();
            if (!$response->isOk) {
                throw new BadRequestHttpException();
            }

            $localPath = Yii::getAlias('@runtime/temp/upload/' . date('Ymd'));
            if (!is_dir($localPath)) {
                FileHelper::createDirectory($localPath);
            }
            $localPath .= '/' . Yii::$app->security->generateRandomString(32) . '.jpg';

            $content = $response->getContent();

            file_put_contents($localPath, $content);
        }

        $response = $this->upload(static::API_SEND_CHECK . $this->accessToken, ['media', $localPath])->send();
        if ($isRemote) {
            @unlink($localPath);
        }

        if ($response->isOk) {
            $content = json_decode($response->getContent(), 1);

            if ($content['errcode'] == 87015 && $this->_retries < 3) {
                $this->_retries++;
                return $this->check($image, $isRemote);
            } else {
                return true;
            }

            if ($content['errcode'] == 0) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new BadRequestHttpException($response->content);
        }
    }

}