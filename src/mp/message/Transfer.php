<?php

namespace abei2017\wx\mp\message;

use abei2017\wx\core\Driver;

class Transfer extends Driver {

    public $type = 'transfer_customer_service';
    public $props = [];
}