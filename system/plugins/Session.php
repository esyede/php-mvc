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
            if (! \Debugger\Debugger::isEnabled()) {
                if (false !== config('config')['development']) {
                    \Debugger\Debugger::dispatch();
                }
            }
            
            @session_start();
            $this->set('session_hash', $this->generateHash());
        } else {
            if (! hash_equals($this->get('session_hash'), $this->generateHash())) {
                $this->destroy();
            }
        }

        $this->set('csrf_token', $this->generateCsrfToken());
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

    public function delete($keys)
    {
        if (is_array($keys)) {
            foreach ($keys as $key) {
                unset($_SESSION[$key]);
            }
        } else {
            unset($_SESSION[$key]);
        }
    }

    public function destroy()
    {
        $_SESSION = [];
        session_destroy();
    }

    private function generateHash()
    {
        $clientIp = getenv('HTTP_CLIENT_IP')
            ?: getenv('HTTP_X_FORWARDED_FOR')
                ?: getenv('HTTP_X_FORWARDED')
                    ?: getenv('HTTP_FORWARDED_FOR')
                        ?: getenv('HTTP_FORWARDED')
                            ?: getenv('REMOTE_ADDR');
        $identity = $this->config['encryption_key'].
            $clientIp.$_SERVER['HTTP_USER_AGENT'].random_int(1, 9999);

        return md5(sha1($identity));
    }

    private function generateCsrfToken()
    {
        if (! function_exists('get_ip')) {
            get_instance()->helper('web');
        }

        $identity = $this->config['encryption_key'].
            random_int(1, 9999).microtime(true);

        return base64_encode(crypt($identity));
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
