<?php
namespace abei2017\wx;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;

class Application extends Component {

    public $conf;

    public $httpClient;

    public $classMap = [
        'core.accessToken'=>'abei2017\wx\core\AccessToken',

        'mp.qrcode'=>'abei2017\wx\mp\qrcode\Qrcode',//  基础
        'mp.shorturl'=>'abei2017\wx\mp\qrcode\Shorturl',//  基础
        'mp.server'=>'abei2017\wx\mp\server\Server',//  服务接口
        'mp.remark'=>'abei2017\wx\mp\user\Remark',//  会员接口
        'mp.user'=>'abei2017\wx\mp\user\User',//  会员接口
        'mp.tag'=>'abei2017\wx\mp\user\Tag',//  标签接口
        'mp.menu'=>'abei2017\wx\mp\menu\Menu',
        'mp.js'=>'abei2017\wx\mp\js\Js',
        'mp.template'=>'abei2017\wx\mp\template\Template',
        'mp.pay'=>'abei2017\wx\mp\payment\Pay',//  支付接口
        'mp.oauth'=>'abei2017\wx\mp\oauth\OAuth',//  支付接口
        'mp.accessToken'=>'abei2017\wx\mp\accessToken\AccessToken',//  支付接口

        'mini.user'=>'abei2017\wx\mini\user\User',
        'mini.pay'=>'abei2017\wx\mini\payment\Pay',
        'mini.qrcode'=>'abei2017\wx\mini\qrcode\Qrcode',
        'mini.template'=>'abei2017\wx\mini\template\Template',
    ];

    public function init(){
        parent::init();
        $this->httpClient = new Client([
            'transport' => 'yii\httpclient\CurlTransport',
        ]);
    }

    public function driver($api,$extra = []){
        $config = [
            'conf'=>$this->conf,
            'httpClient'=>$this->httpClient,
            'extra'=>$extra,
        ];

        $config['class'] = $this->classMap[$api];

        return Yii::createObject($config);
    }
}