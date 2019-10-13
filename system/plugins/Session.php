<?php

defined('BASE') or exit('No direct script access allowed');

class Session
{
    protected $config;

    public function __construct()
    {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        $this->config = config('config');
        $this->init();
    }

    public function init()
    {
        if (PHP_SESSION_ACTIVE !== session_status()) {
            @session_start();
            \Debugger\Debugger::dispatch();
            $this->set('session_hash', $this->generateHash());
        } else {
            if (! hash_equals($this->get('session_hash'), $this->generateHash())) {
                $this->destroy();
            }
        }
    }

    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $name => $data) {
                $_SESSION[$name] = $data;
            }
        } else {
            $_SESSION[$key] = $value;
        }
    }

    public function get($key = null)
    {
        if (is_null($key)) {
            return $_SESSION;
        } else {
            return $this->has($_SESSION[$key]) ? $_SESSION[$key] : false;
        }
    }

    public function has($key)
    {
        return isset($_SESSION[$key]);
    }

    public function delete($key)
    {
        unset($_SESSION[$key]);
    }

    public function destroy()
    {
        $_SESSION = [];
        session_destroy();
    }

    private function generateHash()
    {
        if (! function_exists('get_ip')) {
            get_instance()->helper('web');
        }

        $identity = $this->config['encryption_key'].
        $_SERVER['HTTP_USER_AGENT'].
        get_ip().
        get_browser_lang();

        return md5(sha1(md5($identity)));
    }

    public function setFlash($message, $redirectUrl = null)
    {
        $this->set('flash_message', $message);
        if (! is_null($redirectUrl)) {
            header('Location: '.$redirectUrl);
            exit;
        }

        return true;
    }

    public function getFlash()
    {
        $message = $this->get('flash_message');
        $this->delete('flash_message');

        return $message;
    }

    public function hasFlash()
    {
        return $this->has('flash_message');
    }
}
