<?php

defined('BASE') or exit('No direct script access allowed');

class Login extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->plugin('auth');
        $this->load->plugin('validation');
        $this->load->helper('url');
    }

    public function index()
    {
        $inputs = $this->input->post(['username', 'password']);

        if (filled($inputs)) {
            if (true === $this->auth->login($inputs['username'], $inputs['password'])) {
                redirect('auth/dashboard');
            }
        }
        
        $data = [
            'title' => 'Auth Login',
            'content' => 'Please fill to login',
            'form' => plugin('form'),
        ];

        $this->blade->render('auth.login', $data);
    }


    public function logout()
    {
        if ($this->auth->logout()) {
            return redirect('auth/login');
        }

        return false;
    }
}