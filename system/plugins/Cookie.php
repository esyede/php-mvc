<?php

defined('BASE') or exit('No direct script access allowed');

class Cookie
{
    private $config;
    private $boundary = '--';

    protected $folder = null;
    protected $domain = '';
    protected $secure = false;
    protected $http_only = true;

    public function __construct()
    {
        $this->config = config('config');
        $this->setFolder(storage_path('cookies'));
    }

    public function setFolder($folder)
    {
        if (! is_string($folder)) {
            return false;
        }
        $this->folder = $folder;

        return $this;
    }

    public function setDomain($domain)
    {
        if (! is_string($domain)) {
            return false;
        }
        $this->domain = $domain;

        return $this;
    }

    public function setSecure($secure = false)
    {
        if (! is_bool($secure)) {
            return false;
        }
        $this->secure = $secure;

        return $this;
    }

    public function setHttpOnly($http = true)
    {
        if (! is_bool($http)) {
            return false;
        }
        $this->http_only = $http;

        return $this;
    }

    public function set($name, $value, $time = null)
    {
        if (true == $this->config['cookie_security']) {
            $value .= $this->boundary.md5($value.$this->config['encryption_key']);
        }
        $time = is_numeric($time) ? (time() + (60 * 60 * $time)) : 0;
        setcookie($name, $value, $time, $this->folder, $this->domain, $this->secure, $this->http_only);
    }

    public function get($name)
    {
        if ($this->has($name)) {
            if (true == $this->config['cookie_security']) {
                $slices = explode($this->boundary, $_COOKIE[$name]);
                if (md5($slices[0].$this->config['encryption_key']) == $slices[1]) {
                    return $slices[0];
                } else {
                    // dd('Cookie does not match');
                    return false;
                }
            }

            return $_COOKIE[$name];
        }
    }

    public function delete($name)
    {
        if ($this->has($name)) {
            unset($_COOKIE[$name]);
            setcookie($name, '', time() - 3600, $this->folder, $this->domain);

            return true;
        }

        return false;
    }

    public function has($name)
    {
        return (bool) (isset($_COOKIE[$name]));
    }
}
