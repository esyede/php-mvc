<?php

defined('BASE') or exit('No direct script access allowed');

return [
    // Environment setting
    'development' => true,

    // Storage path, to store cache, logs, cookies etc.
    'storage_path' => 'storage',

    // Default controller folder
    'default_folder' => 'home',

    // Default Controller
    'default_controller' => 'Welcome',

    // Default method name
    'default_method' => 'index',

    // Session/Cookie encryption Key (Change it!)
    'encryption_key' => 'ChangeMe!',

    // Cookie security
    'cookie_security' => true,

    // Send error notification to this email
    'debugger_email' => 'admin@mysite.com',

    // Destination email to send error message when occurs on production mode
    'error_email' => '',

    // Default timezone
    'default_timezone' => 'UTC',
];
