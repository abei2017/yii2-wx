<?php

/*
 * This file is part of the abei2017/yii2-wx.
 *
 * (c) abei <abei@nai8.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace abei2017\wx;

use abei2017\wx\core\Exception;
use Yii;
use yii\base\Component;
use yii\httpclient\Client;

/**
 * bootstrap
 * 此类负责模块其他类的驱动以及相关变量的初始化
 *
 * @link https://nai8.me/study/yii2wx.html
 * @author abei<abei@nai8.me>
 * @package abei2017\wx
 */
class Application extends Component {

    /**
     * yii2-wx配置
     * @var
     */
    public $conf = [];

    /**
     * http客户端
     * @var
     */
    public $httpClient;

    public $httpConf = [
        'transport' => 'yii\httpclient\CurlTransport',
    ];

    /**
     * 类映射
     * @var array
     */
    public $classMap = [
        'core'=>[
            'accessToken'=>'abei2017\wx\core\AccessToken'
        ],

        'mp'=>[
            'base'=>'abei2017\wx\mp\core\Base',    // 二维码
            'qrcode'=>'abei2017\wx\mp\qrcode\Qrcode',    // 二维码
            'shorturl'=>'abei2017\wx\mp\qrcode\Shorturl',    // 短地址
            'server'=>'abei2017\wx\mp\server\Server',    // 服务接口
            'remark'=>'abei2017\wx\mp\user\Remark',  //  会员备注
            'user'=>'abei2017\wx\mp\user\User',  //  会员管理
            'tag'=>'abei2017\wx\mp\user\Tag',    //  会员标签
            'menu'=>'abei2017\wx\mp\menu\Menu',  // 菜单
            'js'=>'abei2017\wx\mp\js\Js',    //  JS
            'template'=>'abei2017\wx\mp\template\Template', //   消息模板
            'pay'=>'abei2017\wx\mp\payment\Pay',//  支付接口
            'mch'=>'abei2017\wx\mp\payment\Mch',//  企业付款
            'redbag'=>'abei2017\wx\mp\payment\Redbag',//  红包
            'oauth'=>'abei2017\wx\mp\oauth\OAuth',//  web授权
            'resource'=>'abei2017\wx\mp\resource\Resource',//  素材
            'kf'=>'abei2017\wx\mp\kf\Kf',//  客服
            'customService'=>'abei2017\wx\mp\kf\CustomService',//  群发
        ],

        'mini'=>[
            'user'=>'abei2017\wx\mini\user\User', // 会员
            'pay'=>'abei2017\wx\mini\payment\Pay', // 支付
            'qrcode'=>'abei2017\wx\mini\qrcode\Qrcode', // 二维码&小程序码
            'template'=>'abei2017\wx\mini\template\Template', // 模板消息
            'custom'=>'abei2017\wx\mini\custom\Customer'
        ]

    ];

    public function init(){
        parent::init();
        $this->httpClient = new Client($this->httpConf);
    }

    /**
     * 驱动函数
     * 此函数主要负责生成相关类的实例化对象并传递相关参数
     *
     * @param $api string 类的映射名
     * @param array $extra  附加参数
     * @throws Exception
     * @return object
     */
    public function driver($api,$extra = []){

        $api = explode('.',$api);
        if(empty($api) OR isset($this->classMap[$api[0]][$api[1]]) == false){
            throw new Exception('很抱歉，你输入的API不合法。');
        }

        //  初始化conf
        if(empty($this->conf)){
            if(isset(Yii::$app->params['wx']) == false){
                throw new Exception('请在yii2的配置文件中设置配置项wx');
            }

            if(isset(Yii::$app->params['wx'][$api[0]]) == false){
                throw new Exception("请在yii2的配置文件中设置配置项wx[{$api[0]}]");
            }

            $this->conf = Yii::$app->params['wx'][$api[0]];
        }

        $config = [
            'conf'=>$this->conf,
            'httpClient'=>$this->httpClient,
            'extra'=>$extra,
        ];

        $config['class'] = $this->classMap[$api[0]][$api[1]];

        return Yii::createObject($config);
    }
}