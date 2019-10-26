<?php

defined('BASE') or exit('No direct script access allowed');

class App
{
    private $loader;

    protected $config;
    protected $url;
    protected $routes;
    protected $controller;
    protected $controller_folder;
    protected $inside_subfolder = false;
    protected $method;
    protected $params = [];

    const VERSION = '1.0.0';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->loader = get_instance();
        $this->config = $this->loader->config('config');
        $date = $this->config['default_timezone'];
        date_default_timezone_set(is_null($date) ? 'UTC' : $date);
        $this->createStorageFolders();

        if (false === \Debugger\Debugger::isEnabled()) {
            $logPath = storage_path('logs');
            $production = (true !== $this->config['development']);
            
            if (filled($this->config['error_email'])) {
                \Debugger\Debugger::$email = $this->config['error_email'];
            }

            \Debugger\Debugger::enable($production, $logPath);
            \Debugger\Debugger::getBar()->addPanel(new \Debugger\GlobalsPanel());
        }

        $this->controller = $this->config['default_controller'];
        $this->method = $this->config['default_method'];
        if ($this->config['default_folder']) {
            $default = $this->config['default_folder'];
            $this->controller_folder = 'controllers'.DS.$default.DS;
        } else {
            $this->controller_folder = 'controllers'.DS;
        }

        $this->url = $this->parseURL();
        $this->routes = $this->loader->config('routes');

        if (false === $this->run()) {
            $this->setController($this->url);
            $this->setAction($this->url);
            $this->setParams($this->url);
        }

        $composer = $this->loader->config('autoload')['composer'];
        $composer = str_replace(['/', '\\'], DS, trim(trim($composer, '/'), '\\'));
        if (filled($composer)) {
            if (! is_file(root_path($composer))) {
                throw new \RuntimeException('Composer autoloader not found: '.root_path($composer));
            }

            $this->loader->file($composer);
        }
        
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    /**
     * Run the framework
     * @return  bool
     */
    private function run()
    {
        if (BASE == '/') {
            $url = ltrim($_SERVER['REQUEST_URI'], '/');
        } else {
            $url = ltrim(str_replace(BASE, '', $_SERVER['REQUEST_URI']), '/');
        }

        $matched = 0;
        foreach ($this->routes as $key => $value) {
            $key = '/^'.str_replace('/', '\/', $key).'$/';

            if (preg_match($key, $url)) {
                ++$matched;
                preg_match_all($key, $url, $matches);
                array_shift($matches);

                $target = explode('/', $value);
                $location = APP.'controllers'.DS;

                if (is_dir($location.$target[0])) {
                    if (is_file($location.$target[0].DS.ucfirst($target[1]).'.php')) {
                        $this->controller = $this->makeURL(ucfirst($target[1]));
                        $this->requireFile(APP.'controllers'.DS.$target[0].DS.$this->controller);
                        $this->controller = new $this->controller();
                    } else {
                        notfound();
                    }
                    $this->method = $target[2];
                } else {
                    if (is_file($location.ucfirst($target[0]).'.php')) {
                        $this->controller = $this->makeURL(ucfirst($target[0]));
                        $this->requireFile($location.$this->controller);
                        $this->controller = new $this->controller();
                    } else {
                        notfound();
                    }

                    $this->method = $target[1];
                }

                foreach ($matches as $key => $value) {
                    $target[$key] = $value[0];
                }

                $this->params = $target ? array_values($target) : [];
            }
        }

        return ($matched > 0);
    }

    /**
     * Set controller based on supplied query string
     * @param  array|null  $url  Query string
     */
    private function setController(array $url = null)
    {
        if (isset($url[0])) {
            $location = APP.'controllers'.DS;
            if (is_dir($location.$url[0])) {
                $this->inside_subfolder = true;
                if (isset($url[1]) && is_file($location.$url[0].DS.$this->makeURL($url[1]).'.php')) {
                    $this->controller = $this->makeURL($url[1]);
                    $this->requireFile($location.$url[0].DS.$this->controller);
                    $this->controller = new $this->controller();
                } else {
                    notfound();
                }
                $this->url = array_diff_key($url, [0, 1]);
            } else {
                if (is_file(APP.$this->controller_folder.$this->makeURL($url[0]).'.php')) {
                    $this->controller = $this->makeURL($url[0]);
                    $this->requireFile(APP.$this->controller_folder.$this->controller);
                    $this->controller = new $this->controller();
                } else {
                    notfound();
                }
                $this->url = array_diff_key($url, [0]);
            }
        } else {
            $this->requireFile(APP.$this->controller_folder.$this->controller);
            $this->controller = new $this->controller();
        }
    }

    /**
     * Set action based on supplied query string
     * @param  array|null  $url  Query string
     */
    private function setAction(array $url = null)
    {
        if (true === $this->inside_subfolder) {
            if (isset($url[2])) {
                if ('index' !== $url[2]) {
                    if (method_exists($this->controller, $url[2])) {
                        $this->method = $url[2];
                    } else {
                        notfound();
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
                        notfound();
                    }
                }
                unset($this->url[1]);
            }
        }
    }

    /**
     * Set parameters based on supplied query string
     * @param  array|null  $url  Query string
     */
    private function setParams(array $url = null)
    {
        $this->params = $url ? array_values($url) : [];
    }

    public function parseURL()
    {
        if (isset($_GET['url'])) {
            $url = filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL);

            return explode('/', $url);
        }
    }

    /**
     * Normalize query string to match with
     * internal class and method naming convention
     * @param   string  $str  Query string
     * @return  string
     */
    public function makeURL($str)
    {
        return ucwords(strtolower(str_replace(['-', '%20'], '_', $str)));
    }

    public function requireFile($path)
    {
        $path = $path.'.php';
        if (is_file($path)) {
            return require_once $path;
        } else {
            throw new \RuntimeException('Failed to load file: '.$path);
        }
    }

    public function getRouteInfo()
    {
        $controller = $this->controller;
        $data = [
            'url'        => implode('/', (array) $this->url),
            'uri'        => $_SERVER['REQUEST_URI'],
            'controller' => is_object($controller) ? get_class($controller) : $controller,
            'method'     => $this->method,
            'params'     => $this->params,
        ];

        return $data;
    }

    /**
     * Create system storage folders
     * @return  bool
     */
    private function createStorageFolders()
    {
        if (! function_exists('storage_path')) {
            $this->loader->helper('path');
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
