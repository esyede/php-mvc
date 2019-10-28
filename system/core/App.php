<?php

defined('BASE') or exit('No direct script access allowed');

class App
{
    const VERSION = '1.0.0';

    private $load;

    protected $config;
    protected $url;
    protected $routes;
    protected $controller;
    protected $folder;
    protected $inside_subfolder = false;
    protected $method;
    protected $params = [];

    public function __construct()
    {
        $this->load = get_instance();
        $this->config = $this->load->config('config');
        $timezone = $this->config['default_timezone'];
        date_default_timezone_set(is_null($timezone) ? 'UTC' : $timezone);
        $this->makeStorageFolders();

        if (false === \Debugger\Debugger::isEnabled()) {
            $logs = storage_path('logs');
            $production = (true !== $this->config['development']);

            if (filled($this->config['error_email'])) {
                \Debugger\Debugger::$email = $this->config['error_email'];
            }

            \Debugger\Debugger::enable($production, $logs);
            \Debugger\Debugger::getBar()->addPanel(new \Debugger\GlobalsPanel());
        }

        $this->controller = $this->config['default_controller'];
        $this->method = $this->config['default_method'];
        if ($this->config['default_folder']) {
            $this->controller_folder = 'controllers'.DS.$this->config['default_folder'].DS;
        } else {
            $this->controller_folder = 'controllers'.DS;
        }

        $this->url = $this->sliceURL();
        $this->routes = $this->load->config('routes');

        if (false === $this->run()) {
            $this->setController($this->url);
            $this->setAction($this->url);
            $this->setParams($this->url);
        }

        $composer = $this->load->config('autoload')['composer'];
        $composer = str_replace(['/', '\\'], DS, trim(trim($composer, '/'), '\\'));
        if (filled($composer)) {
            if (! is_file(root_path($composer))) {
                throw new \RuntimeException('Composer autoloader not found: '.root_path($composer));
            }

            $this->load->file($composer);
        }

        call_user_func_array([$this->controller, $this->method], $this->params);
    }


    private function run()
    {
        if (BASE == '/') {
            $url = ltrim($_SERVER['REQUEST_URI'], '/');
        } else {
            $url = ltrim(str_replace(BASE, '', $_SERVER['REQUEST_URI']), '/');
        }

        $matched = 0;
        foreach ($this->routes as $key => $value) {
            $key = '/^'.str_replace('/', '\/?', $key).'$/';

            if (preg_match($key, $url)) {
                ++$matched;
                preg_match_all($key, $url, $matches);
                array_shift($matches);

                $target = explode('/', $value);
                $location = APP.'controllers'.DS;

                if (is_dir($location.$target[0])) {
                    if (is_file($location.$target[0].DS.ucfirst($target[1]).'.php')) {
                        $this->controller = $this->toUnderscore(ucfirst($target[1]));
                        $this->requireFile(APP.'controllers'.DS.$target[0].DS.$this->controller);
                        $this->controller = new $this->controller;
                    } else {
                        show_404();
                    }
                    $this->method = isset($target[2])
                        ? $target[2]
                        : $this->config['default_method'];
                } else {
                    if (is_file($location.ucfirst($target[0]).'.php')) {
                        $this->controller = $this->toUnderscore(ucfirst($target[0]));
                        $this->requireFile($location.$this->controller);
                        $this->controller = new $this->controller;
                    } else {
                        show_404();
                    }

                    $this->method = isset($target[1])
                        ? $target[1]
                        : $this->config['default_method'];
                }

                foreach ($matches as $key => $value) {
                    $target[$key] = $value[0];
                }

                $this->params = $target ? array_values($target) : [];
            }
        }

        return ($matched > 0);
    }


    private function setController(array $url = null)
    {
        $location = APP.'controllers'.DS;
        if (isset($url[0])) {
            if (is_dir($location.$url[0])) {
                $this->inside_subfolder = true;
                if (isset($url[1]) && is_file($location.$url[0].DS.$this->toUnderscore($url[1]).'.php')) {
                    $this->controller = $this->toUnderscore($url[1]);
                    $this->requireFile($location.$url[0].DS.$this->controller);
                    $this->controller = new $this->controller;
                } else {
                    show_404();
                }
                $this->url = array_diff_key($url, [0, 1]);
            } else {
                if (is_file(APP.$this->controller_folder.$this->toUnderscore($url[0]).'.php')) {
                    $this->controller = $this->toUnderscore($url[0]);
                    $this->requireFile(APP.$this->controller_folder.$this->controller);
                    $this->controller = new $this->controller;
                } else {
                    if (is_file(APP.'controllers'.DS.$this->toUnderscore($url[0]).'.php')) {
                        $this->controller = $this->toUnderscore($url[0]);
                        $this->requireFile(APP.'controllers'.DS.$this->controller);
                        $this->controller = new $this->controller;
                    } else {
                        show_404();
                    }
                }
                $this->url = array_diff_key($url, [0]);
            }
        } else {
            $this->requireFile(APP.$this->controller_folder.$this->controller);
            $this->controller = new $this->controller;
        }
    }


    private function setAction(array $url = null)
    {
        if (true === $this->inside_subfolder) {
            if (isset($url[2])) {
                if ('index' !== $url[2]) {
                    if (method_exists($this->controller, $url[2])) {
                        $this->method = $url[2];
                    } else {
                        show_404();
                    }
                }
                unset($this->url[2]);
            }
        } else {
            if (isset($url[1])) {
                if ('index' !== $url[1]) {
                    if (method_exists($this->controller, $url[1])) {
                        $this->method = $url[1];
                    } else {
                        show_404();
                    }
                }
                unset($this->url[1]);
            }
        }
    }


    private function setParams(array $url = null)
    {
        $params = $url ? array_values($url) : [];
        $this->params = $params;
        return true;
    }

    public function sliceURL()
    {
        if (isset($_GET['url'])) {

            $url = filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL);
            $url = remove_invisible_characters($url);
            $url = array_filter(explode('/', $url));

            return $url;
        }
    }


    public function toUnderscore($str)
    {
        return ucwords(strtolower(str_replace(['-', '%20'], '_', $str)));
    }

    public function requireFile($path)
    {
        $path = $path.'.php';
        if (is_file($path)) {
            return require_once $path;
        }
        throw new \RuntimeException('Failed to load file: '.$path);
    }

    public function getRouteInfo()
    {
        $controller = $this->controller;
        $data = [
            'url' => implode('/', (array) $this->url),
            'uri' => $_SERVER['REQUEST_URI'],
            'controller' => is_object($controller) ? get_class($controller) : $controller,
            'method' => $this->method,
            'params' => $this->params,
        ];

        return $data;
    }


    private function makeStorageFolders()
    {
        if (! function_exists('storage_path')) {
            $this->load->helper('path');
        }

        $created = true;
        if (! is_dir(storage_path())) {
            $created = create_folder(storage_path());
        }

        $folders = ['cache', 'cookies', 'logs', 'uploads', 'views'];
        foreach ($folders as $folder) {
            $folder = storage_path($folder);
            if (! is_dir($folder)) {
                $created = $created && create_folder($folder);
            }
        }

        if (true !== $created) {
            $message = 'Unable to create system storage folder: '.storage_path();
            throw new \RuntimeException($message);
        }

        \Debugger\Debugger::$log_directory = storage_path('logs/');

        return true;
    }
}