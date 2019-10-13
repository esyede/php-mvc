<?php

class Test_model extends Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function findMe()
    {
        dd('This is just a test.');
    }
}
