<?php

defined('BASE') or exit('No direct script access allowed');

class Role_model extends Model
{
    private $table = 'roles';


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


    public function addPermissions($roleId, $permissions)
    {
        $data = ['role_id' => $roleId];
        $success = false;

        if (is_array($permissions)) {
            foreach ($permissions as $permission) {
                $data['permission_id'] = $permission;
                $success = $this->addPermission($data);
            }
        }
        else {
            $data['permission_id'] = $permissions;
            $success = $this->addPermission($data);
        }

        return (false !== $success);
    }


    public function addPermission($data)
    {
        return $this->db
            ->from('permission_roles')
            ->insert($data)
            ->execute();
    }


    public function editPermissions($roleId, $permissions)
    {
        $success = false;

        if ($this->deletePermissions($roleId, $permissions)) {
            $success = $this->addPermissions($roleId, $permissions);
        }

        return (false !== $success);
    }


    public function deletePermissions($roleId, $permissions)
    {
        return $this->db
            ->from('permission_roles')
            ->where('role_id', $roleId)
            ->delete()
            ->execute();
    }


    public function roleWisePermissions($id)
    {
        $query = $this->db
            ->from('permission_roles')
            ->where('role_id', $id)
            ->many();

        return array_map(function ($item) {
            return $item['permission_id'];
        }, $query);
    }


    public function roleWisePermissionDetails($id)
    {
        $self = $this;

        return array_map(function ($item) use ($self) {
            return $self->findPermission($item);
        }, $this->roleWisePermissions($id));
    }


    public function findPermission($id)
    {
        return $this->db
            ->from('permissions')
            ->where('deleted_at IS NULL')
            ->where('id', $id)
            ->one();
    }


    public function roleId($name)
    {
        $role = $this->db
            ->from('roles')
            ->where('deleted_at IS NULL')
            ->where('name', $name)
            ->one();

        return is_array($role) ? $role['id'] : false;
    }
}