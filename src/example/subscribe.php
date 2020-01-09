<?php
/**
 * Created by PhpStorm.
 * User: liyifei
 * Date: 2020/1/2
 * Time: 上午9:46
 */

require 'common.php';


$driver = $sdk->driver('mini.subscribe');
$response = $driver->send('o_DtZ5PSuv51X2whblLf1CJoaPHk', 'NLBy_q8YAiF4V0TLssWEEHIAtSqypoiD9oRsPe0DkrU', '', [
    'name1' => [
        'value' => '李奕飞'
    ],
    'thing2' => [
        'value' => 'test'
    ],
    'date3' => [
        'value' => '2018-01-01',
    ]
]);

print_r($response);