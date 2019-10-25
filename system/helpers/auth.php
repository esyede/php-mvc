<?php

defined('BASE') or exit('No direct script access allowed');

if (! function_exists('auth_check')) {
	function auth_check()
	{
	    $auth = get_instance()->plugin('auth');

	    return $auth->authenticated();
	}
}


if (! function_exists('auth_guest')) {
    function auth_guest()
    {
        $auth = get_instance()->plugin('auth');

        return $auth->guest();
    }
}


if (! function_exists('auth_can')) {
	function auth_can($permissions)
    {
        $auth = get_instance()->plugin('auth');
        
        return $auth->can($permissions);
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
        $auth = get_instance()->plugin('auth');

        return $auth->hasRole($roles);
    }
}