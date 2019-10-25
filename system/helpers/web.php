<?php

defined('BASE') or exit('No direct script access allowed');

// Get client user-agent
if (! function_exists('get_user_agent')) {
    function get_user_agent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }
}

// Get client IP address
if (! function_exists('get_ip')) {
    function get_ip()
    {
        return getenv('HTTP_CLIENT_IP')
            ?: getenv('HTTP_X_FORWARDED_FOR')
                ?: getenv('HTTP_X_FORWARDED')
                    ?: getenv('HTTP_FORWARDED_FOR')
                        ?: getenv('HTTP_FORWARDED')
                            ?: getenv('REMOTE_ADDR');
    }
}

// Check if client's browser is a mobile browser
if (! function_exists('is_mobile')) {
    function is_mobile()
    {
        $pattern = '/(android|avantgo|blackberry|bolt|boost|'.
            'cricket|docomo|fone|hiptop|mini|mobi|palm|phone|'.
            'pie|tablet|up\.browser|up\.link|webos|wos)/i';

        return preg_match($pattern, $_SERVER['HTTP_USER_AGENT']);
    }
}

// Check if current user-agent is a referral
if (! function_exists('is_referral')) {
    function is_referral()
    {
        return isset($_SERVER['HTTP_REFERER']) && '' != $_SERVER['HTTP_REFERER'];
    }
}

// Check if current client's user-agent is a bot
if (! function_exists('is_robot')) {
    function is_robot()
    {
        return isset($_SERVER['HTTP_USER_AGENT'])
        && preg_match('/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT']);
    }
}

// Get client's referral user-agent
if (! function_exists('get_referrer')) {
    function get_referrer()
    {
        return (! isset($_SERVER['HTTP_REFERER']) || '' == $_SERVER['HTTP_REFERER'])
            ? '' : trim($_SERVER['HTTP_REFERER']);
    }
}

// Get list of accepted languages
if (! function_exists('get_langs')) {
    function get_langs()
    {
        $acceptedLang = strtolower(trim($_SERVER['HTTP_ACCEPT_LANGUAGE']));

        return explode(',', preg_replace('/(;q=[0-9\.]+)/i', '', $acceptedLang));
    }
}

// Get accepted language from header
if (! function_exists('get_browser_lang')) {
    function get_browser_lang()
    {
        return substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    }
}

// WHOIS check
if (! function_exists('whois')) {
    function whois($ipAddress, $server = 'whois.internic.net')
    {
        $socket = @fsockopen($server, 43, $errno, $errstr);
        if (! $socket) {
            return false;
        }

        stream_set_blocking($socket, false);
        stream_set_timeout($socket, ini_get('default_socket_timeout'));
        fputs($socket, $ipAddress."\r\n");
        $info = stream_get_meta_data($socket);

        $response = '';
        while (! feof($socket) && ! $info['timed_out']) {
            $response .= fgets($socket, 4096);
            $info = stream_get_meta_data($socket);
        }

        fclose($socket);

        return $info['timed_out'] ? false : trim($response);
    }
}
