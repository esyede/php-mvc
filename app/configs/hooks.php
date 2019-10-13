<?php

defined('BASE') or exit('No direct script access allowed');

return [
    // Sample Hook
    'hook_name' => [
        'filename' => 'FileName', // filename that contains classes and functions
        'class'    => 'ClassName', // class name in the hook file
        'method'   => 'MethodName', // function name in the hook file
        'params'   => ['key' => 'value'], // parameters to pass to function
    ],

    // 'another_hook' => [
    //     'filename' => 'FileName',
    //     'class'    => 'ClassName',
    //     'method'   => 'MethodName',
    //     'params'   => ['key' => 'value']
    // ],
];
