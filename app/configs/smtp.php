<?php

defined('BASE') or exit('No direct script access allowed');

return [
    'server' => 'smtp.mailtrap.io',
    'protocol' => 'ssl', // ssl (secure) OR tcp (standard)
    'port' => 25, // 25 OR 587
    'username' => '3550f81e45440c',
    'password' => 'fecbe20dc64374',
    'hostname' => 'MySite',
    'x-mailer' => 'Mailer Daemon',
    'connection_timeout' => 30,
    'response_timeout' => 8,
    'charset' => 'UTF-8',
];
