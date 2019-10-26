<?php

defined('BASE') or exit('No direct script access allowed');

if (! function_exists('auth_check')) {
    function auth_check()
    {
        return plugin('auth')->authenticated();
    }
}

if (! function_exists('auth_errors')) {
    function auth_errors()
    {
        return plugin('auth')->errors();
    }
}

if (! function_exists('auth_id')) {
    function auth_id()
    {
        return plugin('auth')->userId();
    }
}

if (! function_exists('auth_guest')) {
    function auth_guest()
    {
        return plugin('auth')->guest();
    }
}

if (! function_exists('auth_can')) {
    function auth_can($permissions)
    {
        return plugin('auth')->can($permissions);
    }
}

if (! function_exists('auth_cannot')) {
    function auth_cannot($permissions)
    {
        return (false === auth_can($permissions));
    }
}

if (! function_exists('auth_has_role')) {
    function auth_has_role($roles)
    {
        return plugin('auth')->hasRole($roles);
    }
}

if (! function_exists('auth_logout')) {
    function auth_logout()
    {
        return plugin('auth')->logout();
    }
}
