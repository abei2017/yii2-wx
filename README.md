<p align="center">
    <a href="https://yii2-wx.com">
        <img src="http://www.yii2-wx.com/images/logo.png" height="100" alt="yii2-wx Logo"/>
    </a>
</p>
<p align="center">
    一款服务于yii2的微信SDK（公众号、小程序、开放平台和企业微信）
</p>
<p align="center">
    <img class="latest_stable_version_img" src="https://poser.pugx.org/abei2017/yii2-wx/v/stable">
    <img class="total_img" src="https://poser.pugx.org/abei2017/yii2-wx/downloads">
    <img class="latest_unstable_version_img" src="https://poser.pugx.org/abei2017/yii2-wx/v/unstable">
    <img class="license_img" src="https://poser.pugx.org/abei2017/yii2-wx/license">
</p>

<hr/>


## 文档
- [中文文档](http://www.yii2-wx.com/wiki)

## 系统需求（Requirement）
- PHP >= 5.4
- Composer
- openssl
- fileinfo

## 安装（Installation）
```php
$ composer require "abei2017/yii2-wx" -vvv
```

## 配置（set）
配置参数建议存放到yii2的配置文件中，例如基础版yii2可以如下配置
```php
return [
    'wx'=>[
        //  公众号信息
        'mp'=>[
            //  账号基本信息
            'app_id'  => '', // 公众号的appid
            'secret'  => '', // 公众号的秘钥
            'token'   => '', // 接口的token
            'encodingAESKey'=>'',
            'safeMode'=>0,

            //  微信支付
            'payment'=>[
                'mch_id'        =>  '',// 商户ID
                'key'           =>  '',// 商户KEY
                'notify_url'    =>  '',// 支付通知地址
                'cert_path'     => '',// 证书
                'key_path'      => '',// 证书
            ],

            // web授权
            'oauth' => [
                'scopes'   => 'snsapi_userinfo',// 授权范围
                'callback' => '',// 授权回调
            ],
        ],

        //  小程序配置
        'mini'=>[
            //  基本配置
            'app_id'  => '', 
            'secret'  => '',
            'token' => '',
            'safeMode'=>0,
            'encodingAESKey'=>'',
            //  微信支付
            'payment' => [
                'mch_id'        => '',
                'key'           => '',
            ],
        ]
    ]
];
```
对于配置，请不要修改数据的key值。

## 使用（use）
yii2-wx采用单一接口驱动功能的思路，比如下面的代码将生成一个微信带参数的二维码。

```php
use abei2017\wx\Application;

//  方法一
$qrcode = (new Application())->driver('mp.qrcode');

//  方法二
$conf = Yii::$app->params['wechat'];// 自定义配置数组key（最后一层数组key不可以更改）
$app = new Application(['conf'=>$conf]);

$qrcode = $app->driver('mp.qrcode');
$data = $qrcode->intTemp(3600,9527);// 生成一个数字类临时二维码，有效期为3600秒
```

## 功能实现
**微信公众号**
- [x] 获取接口调用凭证
- [x] 获取微信服务器IP地址
- [x] 验证消息真实性
- [x] 服务器接收实现
- [x] 客户端响应相关接口
- [x] 带参数的二维码
- [x] 用户管理
- [x] 素材管理
- [x] 菜单管理
- [x] 消息模板发送
- [x] web授权机制
- [x] JSSDK
- [x] 微信支付（扫码支付/公众号浏览器支付）
- [x] 企业付款到零钱包
- [x] 现金红包

**微信小程序**
- [x] 小程序码
- [x] 小程序的微信支付
- [x] 小程序模板
- [x] 小程序客服消息


## 开源协议（License）
MIT