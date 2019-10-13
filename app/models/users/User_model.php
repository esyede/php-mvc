<?php

defined('BASE') or exit('No direct script access allowed');

class User_model extends Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function queryTest()
    {
        return $this->db->from('users')
            ->where('banned', 0)
            ->where('fullname %', '%an%')
            ->many();
    }
}
