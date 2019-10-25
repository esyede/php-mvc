<?php
defined('BASE') or exit('No direct script access allowed');

class Auth
{
    protected $loader;
    protected $db;
    protected $session = null;

    public $user = null;
    public $user_id = null;
    public $username = null;
    public $password = null;
    public $roles = 0;
    public $permissions = null;
    public $authenticated = false;


    public function __construct()
    {
        $this->loader = get_instance();
        $this->loader->helper('url');
        $this->init();
    }

    
    protected function init()
    {
        $this->db = $this->loader->database();
        $this->session = $this->loader->plugin('session');

        if ($this->session->has('user_id') && $this->session->get('authenticated')) {
            $this->user_id = $this->session->get('user_id');
            $this->username = $this->session->get('username');
            $this->roles = $this->session->get('roles');
            $this->authenticated = true;
        }

        return $this;
    }


    public function login($username, $password)
    {
        if ($this->validate($username, $password)) {
            $this->user = $this->credentials($username, $password);
            if ($this->user) {
                return $this->setUser();
            } else {
                return $this->error();
            }
        }

        return false;
    }


    protected function validate($username, $password)
    {
        $validation = $this->loader->plugin('validation');
        $validation->init(compact('username', 'password'));
        $validation->rule('required', ['username', 'password']);

        if (true === $validation->validate()) {
            $this->username = $username;
            $this->password = $password;

            bd($_SESSION, 'Session');
            bd(get_segments(), 'get_segments');
            
            return true;
        }

        return false;
    }


    protected function credentials($username, $password)
    {
        $where = [
            'username' => $username,
            'authenticated' => 1,
        ];

        $user = $this->db
            ->from('users')
            ->where('deleted_at IS NULL')
            ->where($where)
            ->one();
        
        if (filled($user) && password_verify($password, $user['password'])) {
            $this->authenticated = true;

            return $user;
        }

        $this->authenticated = false;
        
        return false;
    }


    protected function setUser()
    {
        $this->user_id = $this->user['id'];
        $data = [
            'user_id'       => $this->user['id'],
            'username'      => $this->user['username'],
            'roles'         => $this->userWiseRoles(),
            'authenticated' => true
        ];

        $this->session->set($data);
        $this->authenticated = true;
        redirect('auth/dashboard');
    }


    protected function error()
    {
        return $this->authenticated() ? [] : ['Incorrect username and/or password'];
    }


    public function authenticated()
    {
        return (true === $this->authenticated);
    }


    public function authenticate()
    {
        if (! $this->authenticated()) {
            redirect('auth/login');
        }

        return true;
    }


    public function check($methods = null)
    {
        if (is_array($methods) && count(is_array($methods))) {
            $segment = get_segments();
            foreach ($methods as $method) {
                if ($method == (is_null($segment[2]) ? 'index' : $segment[2])) {
                    return $this->authenticate();
                }
            }
        }
        return $this->authenticate();
    }


    public function guest()
    {
        return ! $this->authenticated();
    }


    public function userId()
    {
        return $this->user_id;
    }


    public function username()
    {
        return $this->username;
    }


    public function roles()
    {
        return $this->roles;
    }


    public function permissions()
    {
        return $this->permissions;
    }


    protected function userWiseRoles()
    {
        $where = ['user_id' => $this->userId()];

        $query = $this->db
            ->from('roles_users')
            ->where($where)
            ->many();

        return array_map(function ($item) {
            return $item['role_id'];
        }, $query);
    }


    public function userRoles()
    {
        $where = [
            'roles_users.user_id' => $this->userId(),
            'roles.status' => 1,
            'deleted_at' => null
        ];
        
        $query = $this->db
            ->from('roles')
            ->join('roles_users', ['roles.id' => 'roles_users.role_id'])
            ->where($where)
            ->select()
            ->execute();

        return array_map(function ($item) {
            return $item['name'];
        }, $query);
    }


    public function userPermissions()
    {
        $where = ['permissions.status' => 1, 'deleted_at' => null];

        $query = $this->db
            ->from('permissions')
            ->join('permission_roles', ['permissions.id' => 'permission_roles.permission_id'])
            ->where('permission_roles.role_id @', (array) $this->roles())
            ->where($where)
            ->groupBy('permission_roles.permission_id')
            ->select()
            ->execute();


        return array_map(function ($item) {
            return $item['name'];
        }, $query);
    }


    public function only(array $methods = [])
    {
        if (is_array($methods) && count(is_array($methods))) {
            foreach ($methods as $method) {
                if ($method == (is_null(get_segments(2)) ? 'index' : get_segments(2))) {
                    return $this->guarded();
                }
            }
        }

        return true;
    }


    public function except(array $methods = [])
    {
        if (is_array($methods) && count(is_array($methods))) {
            foreach ($methods as $method) {
                if ($method == (is_null(get_segments(2)) ? 'index' : get_segments(2))) {
                    return true;
                }
            }
        }

        return $this->guarded();
    }


    public function guarded()
    {
        $this->check();
        $segment = get_segments();
        $routeName = (is_null($segment[2]) ? 'index' : $segment[2]).'-'.$segment[1];
        if ('dashoboard' === $segment[1] || $this->can($routeName)) {
            return true;
        }

        return notfound();
    }


    public function hasRole($roles, $requireAll = false)
    {
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->checkRole($role) && ! $requireAll) {
                    return true;
                } elseif (! $this->checkRole($role) && $requireAll) {
                    return false;
                }
            }
        }
        else {
            return $this->checkRole($roles);
        }

        return $requireAll;
    }


    public function checkRole($role)
    {
        return in_array($role, $this->userRoles());
    }


    public function can($permissions, $requireAll = false)
    {
        if (is_array($permissions)) {
            foreach ($permissions as $permission) {
                if ($this->checkPermission($permission) && ! $requireAll) {
                    return true;
                } elseif (! $this->checkPermission($permission) && $requireAll) {
                    return false;
                }
            }
        }
        else {
            return $this->checkPermission($permissions);
        }

        return $requireAll;
    }


    public function cannot($permissions, $requireAll = false)
    {
        return (false === $this->can($permissions, $requireAll));
    }


    public function checkPermission($permission)
    {
        return in_array($permission, $this->userPermissions());
    }


    public function logout()
    {
        $this->session->delete(['user_id', 'username', 'authenticated']);
        $this->session->destroy();

        return true;
    }
}