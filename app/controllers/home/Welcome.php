<?php

defined('BASE') or exit('No direct script access allowed');

class Welcome extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->plugin('view');
    }

    public function index()
    {
        $title = 'home';
        $content = 'This is your '.$title.' page';
        // $this->view->clearCache();
        $this->view->render('home.index', compact('title', 'content'));
    }
}
