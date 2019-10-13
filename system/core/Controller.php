<?php

defined('BASE') or exit('No direct script access allowed');

class Controller extends Loader
{
    protected $load;
    protected $language;

    private $autoload;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->load = $this;
        $this->autoload = $this->load->config('autoload');
        $this->autoloadHelpers();
        $this->autoloadPlugins();
    }

    /**
     * Autoload user's defined helpers (in config/autoload.php).
     *
     * @throws \RuntimeException Throws RuntimeException on failure
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
     * Autoload user's defined plugins (in config/autoload.php).
     *
     * @throws \RuntimeException Throws RuntimeException on failure
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
}
