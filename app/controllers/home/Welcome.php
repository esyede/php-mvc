<?php

defined('BASE') or exit('No direct script access allowed');

class Welcome extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $title = 'home';
        $content = 'This is your '.$title.' page';

        // $this->blade->clearCache();
        $this->blade->render('home.index', compact('title', 'content'));
    }
}
