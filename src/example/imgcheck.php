<?php
/**
 * Created by PhpStorm.
 * User: leeyifiei
 * Date: 2019/9/27
 * Time: 10:25 AM
 */
require 'common.php';


$driver = $sdk->driver('mini.imgcheck');
$response = $driver->check('https://ss2.bdstatic.com/8_V1bjqh_Q23odCf/pacific/1768717746.jpg', true);

print_r($response);
