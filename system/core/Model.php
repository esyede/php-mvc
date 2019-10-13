<?php

defined('BASE') or exit('No direct script access allowed');

class Model extends \Loader
{
    public $db;
    protected $load;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->load = parent::getInstance();
        $env = $this->load->config('config');
        $config = $this->load->config('database');
        $this->db = $this->load->database();
    }
}
