<?php

defined('BASE') or exit('No direct script access allowed');

// Require compatibility functions and debugger classes
require __DIR__.'/compat.php';
require __DIR__.'/Debugger/bootstrap.php';

use Debugger\Debugger;

$errorTemplate = APP.'views'.DS.'errors'.DS.'debugger.php';
Debugger::$error_template = is_file($errorTemplate) ? $errorTemplate : null;
Debugger::$strict_mode = true;
Debugger::isEnabled() or Debugger::enable();

// Convenient way to initialize Loader class (singleton)
if (! function_exists('get_instance')) {
    function get_instance()
    {
        return \Loader::getInstance();
    }
}

if (! function_exists('show_error')) {
    function show_error($message, $code = 500)
    {
        $code = abs($code);
        if ($code < 100) {
            $exit = $code + 9;
            $code = 500;
        } else {
            $exit = 1;
        }

        require_once SYSTEM.'core'.DS.'Response.php';
        $response = new Response();

        $response->setStatus($code);
        $data = $response->getStatus($code);
        $data['message'] = $message;

        $errFile = APP.'views'.DS.'errors'.DS.'general.php';
        if (! is_file($errFile)) {
            $errFile = SYSTEM.'core'.DS.'Debugger'.DS.
                'assets'.DS.'Debugger'.DS.'errors'.DS.'general.php';
        }
        extract($data);
        require_once $errFile;
        exit($exit);
    }
}

// Convenient way to show 404 error page
if (! function_exists('notfound')) {
    function notfound()
    {
        $message = "We're sorry! The page you have requested ".
            "cannot be found on this server. ".
            "The page may be deleted or no longer exists.";
        show_error($message, 404);
    }
}

// Convenient way to add timer (for benchmarking)
if (! function_exists('timer')) {
    function timer($name = null)
    {
        $name = is_string($name) ? $name : null;

        return Debugger::timer($name);
    }
}

// Helper function used to write log message
if (! function_exists('write_log')) {
    function write_log($message, $type = 'info')
    {
        $type = is_string($type) ? strtolower($type) : 'info';
        $types = ['info', 'warning', 'error', 'debug', 'exception', 'critical'];
        $type = in_array($type, $types) ? $type : 'info';

        return Debugger::log($message, $type);
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

// Helper function used to create new folder (with aaded index file for security)
if (! function_exists('create_folder')) {
    function create_folder($path, $chmod = 0755)
    {
        $path = str_replace('/', DS, rtrim(rtrim($path, '/'), DS));
        $mask = umask(0);
        if (! mkdir($path, $chmod, true)) {
            return false;
        }
        
        @touch(rtrim($path, DS).DS.'index.html');
        umask($mask);

        return true;
    }
}

// Convenient way to autoload plugins
if (! function_exists('plugin')) {
    function plugin($plugin, $params = null)
    {
        return get_instance()->plugin($plugin, $params);
    }
}

// Convenient way to autoload helpers
if (! function_exists('helper')) {
    function helper($helper)
    {
        get_instance()->helper($helper);
    }
}
