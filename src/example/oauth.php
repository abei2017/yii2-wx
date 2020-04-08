<?php
/**
 * Created by PhpStorm.
 * User: leeyifiei
 * Date: 2020/4/8
 * Time: 9:50 AM
 */

require 'common.php';

/**
 * @var \abei2017\wx\mp\oauth\OAuth $driver
 */
$driver = $sdk->driver('mp.oauth');

var_dump($driver->qrcode());