<?php

defined('BASE') or exit('No direct script access allowed');

if (! function_exists('root_path')) {
    function root_path($path = null)
    {
        $path = ltrim($path, '/');
        $path = ltrim($path, '\\');
        $path = ROOT.str_replace('/', DS, rtrim($path, '/\\'));

        return $path;
    }
}

// Get upload app path (absolute)
if (! function_exists('app_path')) {
    function app_path($path = null)
    {
        $path = ltrim($path, '/');
        $path = ltrim($path, '\\');
        $path = APP.str_replace('/', DS, rtrim($path, '/\\'));

        return $path;
    }
}

// Get upload app path (absolute)
if (! function_exists('system_path')) {
    function system_path($path = null)
    {
        $path = ltrim($path, '/');
        $path = ltrim($path, '\\');
        $path = SYSTEM.str_replace('/', DS, rtrim($path, '/\\'));

        return $path;
    }
}

// Get setorage path (absolute)
if (! function_exists('storage_path')) {
    function storage_path($path = null)
    {
        $storage = get_instance()->config('config');
        $storage = $storage['storage_path'];
        $path = trim($path, '/');
        $path = trim($path, '\\');
        $path = str_replace('/', DS, rtrim(ROOT.$storage.DS.$path, '/\\'));

        return $path;
    }
}

// Get upload storage path (absolute)
if (! function_exists('uploads_path')) {
    function uploads_path($path = null)
    {
        $path = ltrim($path, '/');
        $path = ltrim($path, '\\');
        $path = storage_path('uploads'.DS.str_replace('/', DS, rtrim($path, '/\\')));

        return $path;
    }
}
