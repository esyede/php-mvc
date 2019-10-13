<?php

defined('BASE') or exit('No direct script access allowed');

class Hook
{
    private $hook;
    private $class = null;
    private $method = null;
    private $params = [];

    public function __construct()
    {
        $this->hook = config('hooks');
    }

    public function run($name)
    {
        // Call declared hooks defined in the config file
        if (isset($this->hook[$name]) && is_array($this->hook[$name])) {
            get_instance()->hook($this->hook[$name]['filename']);

            if (array_key_exists('class', $this->hook[$name])) {
                $this->class = $this->hook[$name]['class'];
            }

            if (array_key_exists('method', $this->hook[$name])) {
                $this->method = $this->hook[$name]['method'];
            }

            if (array_key_exists('params', $this->hook[$name])) {
                foreach ($this->hook[$name]['params'] as $key => $val) {
                    $this->params[$key] = $val;
                }
            }

            $called = false;
            if (! is_null($this->class) && ! is_null($this->method)) {
                $called = call_user_func_array([$this->class, $this->method], $this->params);
            } elseif (! is_null($this->method)) {
                $called = call_user_func_array($this->method, $this->params);
            }

            if (false === $called) {
                throw new \BadMethodCallException('Unable to call method: '.$this->method);
            }

            $this->reset();
        }
    }

    public function addParam(array $params = [])
    {
        foreach ($params as $key => $val) {
            $this->params[$key] = $val;
        }
    }

    private function reset()
    {
        $this->class = null;
        $this->method = null;
        $this->params = [];
    }
}
