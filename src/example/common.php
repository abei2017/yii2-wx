<?php
/**
 * Created by PhpStorm.
 * User: leeyifiei
 * Date: 2019/9/27
 * Time: 9:20 AM
 */

require dirname(__FILE__) . '/../../vendor/autoload.php';

require_once(dirname(__FILE__) . '/../../vendor/yiisoft/yii2/Yii.php');
@(Yii::$app->charset = 'UTF-8');

$application = new yii\web\Application([
    'id' => 'example',
    'basePath' => dirname(__FILE__),
    'runtimePath' => dirname(__FILE__) . '/runtime',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
            'cachePath' => '@runtime/cache'
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info', 'error', 'warning'],
//                  'categories' => ['yii\db\*'],
                    'logVars' => [],
                    'logFile' => '@runtime/log/app.log'
                ],
            ]
        ]
    ]
]);



$conf = file_get_contents('./config.txt');
$conf = json_decode($conf, 1);

$sdk = new \abei2017\wx\Application([
    'conf' => [
        'app_id' => $conf['appkey'],
        'secret' => $conf['appsecret'],
    ]
]);

