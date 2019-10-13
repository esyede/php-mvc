<?php

defined('BASE') or exit('No direct script access allowed');

// Require compatibility functions and debugger classes
require __DIR__.'/compat.php';
require __DIR__.'/Debugger/bootstrap.php';

use Debugger\Debugger;

$error = APP.'views'.DS.'errors'.DS.'500.php';
Debugger::enable();
Debugger::$error_template = is_file($error) ? $error : null;
Debugger::$strict_mode = true;

// Convenient way to initialize Loader class (singleton)
if (! function_exists('get_instance')) {
    function get_instance()
    {
        return \Loader::getInstance();
    }
}

// Helper function used to create initial system's storage folders
if (! function_exists('create_folder')) {
    function create_folder($path, $chmod = 0755)
    {
        $path = str_replace('/\\', DS.DS, rtrim(ROOT.$path, '/\\')).DS;
        $created = @mkdir($path, $chmod, true) && touch($path.'index.html');

        return $created;
    }
}

// Convenient way to load config files
if (! function_exists('config')) {
    function config($config)
    {
        return get_instance()->config($config);
    }
}

// Convenient way to debug code (dump, prettify, die)
if (! function_exists('dd')) {
    function dd($var)
    {
        array_map('\Debugger\Debugger::dump', func_get_args());
        if (! Debugger::$production_mode) {
            exit;
        }
    }
}

// Convenient way to debug code via debugbar (dump, prettify, continue)
if (! function_exists('bd')) {
    function bd($var, $title = null)
    {
        return Debugger::barDump($var, $title);
    }
}

// Convenient way to escape view variables
if (! function_exists('e')) {
    function e($str, $charset = null)
    {
        $charset = is_null($charset) ? 'UTF-8' : $charset;

        return htmlspecialchars($str, ENT_QUOTES, $charset);
    }
}

// Convenient way to show 404 error page
if (! function_exists('notfound')) {
    function notfound()
    {
        $error = APP.'views'.DS.'errors'.DS.'404.php';
        if (is_file($error)) {
            require_once $error;
            die();
        } else {
            $error = SYSTEM.'core'.DS.'Debugger'.DS.'assets'.DS.'errors'.DS.'404.php';
            require_once $error;
            die();
        }
    }
}

if (! function_exists('blank')) {
    function blank($value)
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return '' === trim($value);
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof \Countable) {
            return 0 === count($value);
        }

        return empty($value);
    }
}

// Convenient way to determine if variable is filled (not blank)
if (! function_exists('filled')) {
    function filled($value)
    {
        return ! blank($value);
    }
}

// Convenient way to autoload plugins
if (! function_exists('plugin')) {
    function plugin($plugin, $params = null)
    {
        get_instance()->plugin($plugin, $params);
    }
}

// Convenient way to autoload helpers
if (! function_exists('helper')) {
    function helper($helper)
    {
        get_instance()->helper($helper);
    }
}
