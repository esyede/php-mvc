<?php

defined('BASE') or exit('No direct script access allowed');

class Model extends \Loader
{
    protected $db;

    public function __construct()
    {
        $this->db = parent::getInstance()->database();
    }
}
