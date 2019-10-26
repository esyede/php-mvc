<?php

defined('BASE') or exit('No direct script access allowed');

class Loader
{
    protected $config = [];
    protected $language = [];

    private static $instance;

    public function __construct()
    {
        self::$instance = $this;
    }

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function model($model, $alias = null)
    {
        $path = app_path('models/');
        $class = null;
        $model = trim(str_replace(['.', '/'], DS, $model), DS);

        if (false !== strpos($model, DS)) {
            $parts = explode(DS, $model);
            $class = ucfirst(end($parts));
            array_pop($parts);
            $model = $path.implode(DS, $parts).DS.$class.'.php';
        } else {
            $class = ucfirst($model);
            $model = $path.$class.'.php';
        }

        if (is_file($model)) {
            require_once $model;

            if (is_null($alias)) {
                $this->$model = new $class();
            } else {
                $this->$alias = new $class();
            }

            return true;
        }
        throw new \RuntimeException('Model not found: '.$model);
    }

    public function view($view, array $data = [], $returnOnly = false)
    {
        $view = str_replace(['.', '/'], DS, $view);
        $view = app_path('views/'.$view.'.php');

        if (! is_file($view)) {
            throw new \RuntimeException('View not found: '.$view);
        }

        if ($returnOnly) {
            return file_get_contents($view);
        }

        extract($data);

        return require_once $view;
    }

    public function plugin($plugin, array $params = null)
    {
        $app = app_path('plugins/'.ucfirst($plugin));
        $system = system_path('plugins/'.ucfirst($plugin));

        if (is_file($app.DS.ucfirst($plugin).'.php') || is_file($app.'.php')) {
            if (is_file($app.DS.ucfirst($plugin).'.php')) {
                require_once $app.DS.ucfirst($plugin).'.php';

                if (is_null($params)) {
                    $this->$plugin = new $plugin();
                } else {
                    $this->$plugin = new $plugin($params);
                }
            } else {
                if (is_file($app.'.php')) {
                    require_once $app.'.php';

                    if (is_null($params)) {
                        $this->$plugin = new $plugin();
                    } else {
                        $this->$plugin = new $plugin($params);
                    }
                }
            }

            return $this->$plugin;
        } elseif (is_file($system.DS.ucfirst($plugin).'.php')
        || is_file($system.'.php')) {
            if (is_file($system.DS.ucfirst($plugin).'.php')) {
                require_once $system.DS.ucfirst($plugin).'.php';

                if (is_null($params)) {
                    $this->$plugin = new $plugin();
                } else {
                    $this->$plugin = new $plugin($params);
                }
            } else {
                if (is_file($system.'.php')) {
                    require_once $system.'.php';

                    if (is_null($params)) {
                        $this->$plugin = new $plugin();
                    } else {
                        $this->$plugin = new $plugin($params);
                    }
                }
            }

            return $this->$plugin;
        }
        throw new \RuntimeException('Plugin not found: '.$plugin);
    }

    public function helper($helper)
    {
        $location = 'helpers'.DS.$helper;
        if (is_file(APP.$location.'.php')) {
            require_once APP.$location.'.php';
        } elseif (is_file(SYSTEM.$location.'.php')) {
            require_once SYSTEM.$location.'.php';
        } else {
            throw new \RuntimeException('Helper not found: '.$helper);
        }
    }

    public function config($config)
    {
        $location = APP.'configs'.DS.$config.'.php';
        if (is_file($location)) {
            if (! array_key_exists($config, $this->config)) {
                $this->config[$config] = require $location;
            }

            return $this->config[$config];
        }
        throw new \RuntimeException('Config file not found: '.$config);
    }

    public function database()
    {
        require_once system_path('db/Database.php');
        $development = (bool) $this->config('config')['development'];
        $config = $this->config('database', $development);

        return \Database::init($config);
    }

    public function schema()
    {
        require_once system_path('db/Schema.php');
        $config = $this->config('database');

        return new \Schema($config);
    }

    public function hook($hook)
    {
        $location = app_path('hooks/'.ucfirst($hook).'.php');
        if (is_file($location)) {
            require_once $location;
        } else {
            throw new \RuntimeException('Hook file not found: '.$hook);
        }
    }

    public function language($language, $path = null)
    {
        $location = $language;
        if (is_null($path)) {
            $default = $this->config('language');
            $default = $default['default_language'];
            $location = 'lang'.DS.$default;
        } else {
            $location = 'lang'.$path.DS;
        }

        $appLang = app_path('configs'.DS.$location.DS.$language.'.ini');
        $sysLang = system_path($location.DS.$language.'.ini');

        if (is_file($appLang)) {
            $this->language = parse_ini_file($appLang, true, INI_SCANNER_RAW);
        } elseif (is_file($sysLang)) {
            $this->language = parse_ini_file($sysLang, true, INI_SCANNER_RAW);
        } else {
            throw new \RuntimeException('Language file not found: '.$language);
        }

        return $this->language;
    }

    public function file($path)
    {
        if (is_file(root_path($path))) {
            return require_once root_path($path);
        }

        throw new \RuntimeException('Unable to load file: '.root_path($path));
    }
}
