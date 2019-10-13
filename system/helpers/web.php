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
        $ip = false;
        if (isset($_SERVER['HTTP_CLIENT_IP'])
        && valid_ip($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
        && filled($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($ips as $addr) {
                if (valid_ip($addr)) {
                    $ip = $addr;
                    break;
                }
            }
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])
        && valid_ip($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])
        && valid_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])
        && valid_ip($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])
        && valid_ip($_SERVER['HTTP_FORWARDED'])) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_VIA'])
        && valid_ip($_SERVER['HTTP_VIAD'])) {
            $ip = $_SERVER['HTTP_VIA'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])
        && filled($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return (false === $ip) ? '0.0.0.0' : $ip;
    }
}

// Validate client IP address
if (! function_exists('valid_ip')) {
    function valid_ip($ip)
    {
        $ip = trim($ip);
        if (filled($ip) && -1 != ip2long($ip)) {
            $reserved = [
                ['0.0.0.0', '2.255.255.255'],
                ['10.0.0.0', '10.255.255.255'],
                ['127.0.0.0', '127.255.255.255'],
                ['169.254.0.0', '169.254.255.255'],
                ['172.16.0.0', '172.31.255.255'],
                ['192.0.2.0', '192.0.2.255'],
                ['192.168.0.0', '192.168.255.255'],
                ['255.255.255.0', '255.255.255.255'],
            ];

            foreach ($reserved as $res) {
                $min = ip2long($res[0]);
                $max = ip2long($res[1]);
                if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) {
                    return false;
                }
            }

            return true;
        }

        return false;
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
