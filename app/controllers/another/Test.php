<?php

defined('BASE') or exit('No direct script access allowed');

class Test extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->plugin('view');
    }

    public function index($number = null, $alphabetic = null)
    {
        $title = 'Test';
        $content = 'This is your '.$title.' page';
        $content .= '. Supplied parameters: '.$number.$alphabetic;
        
        // $this->view->clearCache();
        $this->view->render('test.index', compact('title', 'content'));
    }

    public function migration()
    {
        // $this->load->model('migrations.migrate_users', 'mig_users');

        // $this->mig_users->up();
        // $this->mig_users->seed();
        // $this->mig_users->down();
    }
}
