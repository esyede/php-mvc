<?php

defined('BASE') or exit('No direct script access allowed');

class Migrate_auth extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->load->plugin('migration');

        if ($this->migration->current()) {
            $this->seedUsersTable();
        } else {
            show_error($this->migration->errors());
        }
    }

    private function seedUsersTable()
    {
        $this->load->plugin('faker');

        $data = [
            'id' => 1,
            'name' => $this->faker->randomName(),
            'username' => 'admin',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'authenticated' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->load->database()
            ->from('users')
            ->insert($data)
            ->execute();
    }
}
