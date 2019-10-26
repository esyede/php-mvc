<?php

defined('BASE') or exit('No direct script access allowed');

class Controller extends Loader
{
    private $autoload;
    
    protected $load;
    protected $language;
    protected $input;
    protected $response;
    protected $blade = null;

    public function __construct()
    {
        $this->load = $this;
        $this->autoload = $this->load->config('autoload');
        if (is_null($this->blade)) {
            $this->load->file('system/plugins/Blade.php');
            $this->blade = new Blade();
        }

        $this->autoloadAdditionalCoreClasses();
        $this->autoloadHelpers();
        $this->autoloadPlugins();
    }

    /**
     * Autoload helper files
     */
    public function autoloadHelpers()
    {
        if (count($this->autoload['helpers']) > 0) {
            foreach ($this->autoload['helpers'] as $helper) {
                $helperName = ucfirst($helper);
                $this->load->helper($helperName);
            }
        }
    }

    /**
     * Autoload plugin files
     */
    public function autoloadPlugins()
    {
        if (count($this->autoload['plugins']) > 0) {
            foreach ($this->autoload['plugins'] as $plugin) {
                $pluginName = ucfirst($plugin);
                $this->$plugin = $this->load->plugin($pluginName);
            }
        }
    }

    public function autoloadAdditionalCoreClasses()
    {
        $classes = ['input', 'response'];
        foreach ($classes as $class) {
            $uppercased = ucfirst($class);
            $this->load->file('system/core/'.$uppercased.'.php');
            
            try {
                $this->$class = new $uppercased();
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }
}
