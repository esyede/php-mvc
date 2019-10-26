<?php

defined('BASE') or exit('No direct script access allowed');

class Permission_model extends Model
{
    private $table = 'permissions';

    public function __construct()
    {
        parent::__construct();
    }

    public function find($id)
    {
        return $this->db
            ->from($this->table)
            ->where('deleted_at IS NULL')
            ->where('id', $id)
            ->one();
    }

    public function all()
    {
        return $this->db
            ->from($this->table)
            ->where('deleted_at IS NULL')
            ->many();
    }

    public function add(array $data)
    {
        return $this->db
            ->from($this->table)
            ->insert($data)
            ->execute();
    }

    public function edit(array $data)
    {
        return $this->db
            ->from($this->table)
            ->where('id', $data['id'])
            ->update($data)
            ->execute();
    }

    public function delete($id)
    {
        $data = ['deleted_at' => date('Y-m-d H:i:s')];

        if (filled($this->find($id))) {
            return $this->db
                ->from($this->table)
                ->where('id', $id)
                ->update($data)
                ->execute();
        }

        return false;
    }
}
