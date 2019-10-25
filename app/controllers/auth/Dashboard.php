<?php

defined('BASE') or exit('No direct script access allowed');

class Dashboard extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->plugin('auth');
        // $this->auth->guarded();
    }

    public function index()
    {
        $title = 'dashboard';
        $content = 'This is your '.$title.' page';
        
        $this->blade->render('auth.dashboard', compact('title', 'content'));
    }
}