<?php

defined('BASE') or exit('No direct script access allowed');

class User_model extends Model
{
    private $table = 'users';


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


    public function add($data)
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

        return $this->db
            ->from($this->table)
            ->insert($data)
            ->execute();
    }


    public function edit($data)
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


    public function addRoles($userId, $roles)
    {
        $data = ['user_id' =>  $userId];
        $success = false;

        if (is_array($roles)) {
            foreach ($roles as $role) {
                $data['role_id'] = $role;
                $success = $this->addRole($data);
            }
        } else {
            $data['role_id'] = $roles;
            $success = $this->addRole($data);
        }

        return (false !== $success);
    }


    public function addRole($data)
    {
        return $this->db
            ->from('roles_users')
            ->insert($data)
            ->execute();
    }


    public function editRoles($userId, $roles)
    {
        $success = false;

        if ($this->deleteRoles($userId, $roles)) {
            $success = $this->addRoles($userId, $roles);
        }

        return (false !== $success);
    }


    public function deleteRoles($userId, $roles)
    {
        return $this->db
            ->from('roles_users')
            ->where('user_id', $userId)
            ->delete()
            ->execute();
    }


    public function deleteRole($userId, $roleId)
    {
        return $this->db
            ->from('roles_users')
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->delete()
            ->execute();
    }


    public function userWiseRoles($id)
    {
        $query = $this->db
            ->from('roles_users')
            ->where('user_id', $id)
            ->many();

        return array_map(function ($item) {
            return $item['role_id'];
        }, $query);
    }


    public function userWiseRoleDetails($id)
    {
        $self = $this;

        return array_map(function ($item) use ($self) {
            return $self->findRole($item);
        }, $this->userWiseRoles($id));
    }


    public function findRole($id)
    {
        return $this->db
            ->from('roles')
            ->where('deleted_at IS NULL')
            ->where('id', $id)
            ->one();
    }
}