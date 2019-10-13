<?php

defined('BASE') or exit('No direct script access allowed');

return [
    'home'               => 'home/welcome/index',
    'migration'          => 'another/test/migration',
    'test(/\d+)?(/\w+)?' => 'another/test/index/$1',
];
