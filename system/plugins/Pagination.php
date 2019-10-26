<?php

defined('BASE') or exit('No direct script access allowed');

class Pagination
{
    public $config = [];
    public $base_url;
    public $per_page;
    public $total_rows;
    public $uri_segment;
    public $total_pages;
    public $current_page;

    public $main_tag_open = '<div><ul style="list-style:none;">';
    public $main_tag_close = '</ul></div>';

    public $first_link = 'First';
    public $last_link = 'Last';

    public $first_tag_open = '<li style="display: inline; padding: 3px 7px;'.
        'margin: 0 5px; border: 1px solid #bc5858;">';
    public $first_tag_close = '</li>';

    public $prev_link = 'Prev';
    public $prev_tag_open = '<li style="display: inline; padding: 3px 7px;'.
        'margin: 0 5px; border: 1px solid #bc5858;">';
    public $prev_tag_close = '</li>';

    public $next_link = 'Next';
    public $next_tag_open = '<li style="display: inline; padding: 3px 7px;'.
        'margin: 0 5px; border: 1px solid #bc5858;">';
    public $next_tag_close = '</li>';

    public $last_tag_open = '<li style="display: inline; padding: 3px 7px;'.
        'margin: 0 5px; border: 1px solid #bc5858;">';
    public $last_tag_close = '</li>';

    public $cur_tag_open = '<li style="display: inline; padding: 3px 7px;'.
        'margin: 0 5px; border: 1px solid #bc5858; background-color: #bc5858; color: #fff;">';
    public $cur_tag_close = '</li>';

    public $num_tag_open = '<li style="display: inline; padding: 3px 7px;'.
        'margin: 0 5px; border: 1px solid #bc5858;">';
    public $num_tag_close = '</li>';

    public function __construct() { }

    public function init($config)
    {
        $this->config = $config;

        try {
            // Set base pagination url
            if (! array_key_exists('base_url', $this->config)) {
                $message = "The 'base_url' config must be set before using this library";
                throw new \Exception($message);
            }

            $this->base_url = $config['base_url'];

            // Set item display per page
            if (array_key_exists('per_page', $this->config)) {
                $this->per_page = $this->config['per_page'];
            } else {
                $this->per_page = 0;
            }

            // Set total record count
            if (array_key_exists('total_rows', $this->config)) {
                $this->total_rows = $this->config['total_rows'];
            } else {
                $this->total_rows = 1;
            }

            // Set uri segment of page number
            if (array_key_exists('uri_segment', $this->config)) {
                $this->uri_segment = $this->config['uri_segment'];
            } else {
                $message = "The 'uri_segment' config must be set before using this library";
                throw new Exception($message);
            }

            if (array_key_exists('per_page', $this->config)
            && array_key_exists('total_rows', $this->config)) {
                if (0 != $this->total_rows % $this->per_page) {
                    $this->total_pages = ceil($this->total_rows / $this->per_page);
                } else {
                    $this->total_pages = $this->total_rows / $this->per_page;
                }
            } else {
                $message = "The 'per_page' and 'total_rows' config ".
                    'must be set before using this library';
                throw new \Exception($message);
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        if (array_key_exists('main_tag_open', $this->config)) {
            $this->main_tag_open = $this->config['main_tag_open'];
        }

        if (array_key_exists('main_tag_close', $this->config)) {
            $this->main_tag_close = $this->config['main_tag_close'];
        }

        if (array_key_exists('first_link', $this->config)) {
            $this->first_link = $this->config['first_link'];
        }

        if (array_key_exists('last_link', $this->config)) {
            $this->last_link = $this->config['last_link'];
        }

        if (array_key_exists('first_tag_open', $this->config)) {
            $this->first_tag_open = $this->config['first_tag_open'];
        }

        if (array_key_exists('first_tag_close', $this->config)) {
            $this->first_tag_close = $this->config['first_tag_close'];
        }

        if (array_key_exists('prev_link', $this->config)) {
            $this->prev_link = $this->config['prev_link'];
        }

        if (array_key_exists('prev_tag_open', $this->config)) {
            $this->prev_tag_open = $this->config['prev_tag_open'];
        }

        if (array_key_exists('prev_tag_close', $this->config)) {
            $this->prev_tag_close = $this->config['prev_tag_close'];
        }

        if (array_key_exists('next_link', $this->config)) {
            $this->next_link = $this->config['next_link'];
        }

        if (array_key_exists('next_tag_open', $this->config)) {
            $this->next_tag_open = $this->config['next_tag_open'];
        }

        if (array_key_exists('next_tag_close', $this->config)) {
            $this->next_tag_close = $this->config['next_tag_close'];
        }

        if (array_key_exists('last_tag_open', $this->config)) {
            $this->last_tag_open = $this->config['last_tag_open'];
        }

        if (array_key_exists('last_tag_close', $this->config)) {
            $this->last_tag_close = $this->config['last_tag_close'];
        }

        if (array_key_exists('cur_tag_open', $this->config)) {
            $this->cur_tag_open = $this->config['cur_tag_open'];
        }

        if (array_key_exists('cur_tag_close', $this->config)) {
            $this->cur_tag_close = $this->config['cur_tag_close'];
        }

        if (array_key_exists('num_tag_open', $this->config)) {
            $this->num_tag_open = $this->config['num_tag_open'];
        }

        if (array_key_exists('num_tag_close', $this->config)) {
            $this->num_tag_close = $this->config['num_tag_close'];
        }
    }

    public function getLinks()
    {
        if (is_numeric(get_segments($this->uri_segment))) {
            $this->current_page = get_segments($this->uri_segment);
        } else {
            $this->current_page = 1;
        }

        $result = '';
        $lastPage = 1;

        if ($this->total_pages > 1) {
            $result = $this->main_tag_open;

            if ($this->current_page > 1) {
                // First-page link
                if (false !== $this->first_link) {
                    $result .= $this->first_tag_open.'<a href="'.$this->base_url.'/1">'.
                        $this->first_link.'</a>'.$this->first_tag_close;
                }

                // Previous-page link
                $result .= $this->prev_tag_open.'<a href="'.$this->base_url.
                    '/'.($this->current_page - 1).'">'.$this->prev_link.
                    '</a>'.$this->prev_tag_close;
            }

            if ($this->total_pages > 4 && $this->current_page > 4) {
                for ($i = $this->current_page - 3; $i < $this->current_page; ++$i) {
                    $result .= $this->num_tag_open.'<a href="'.$this->base_url.
                        '/'.$i.'">'.$i.'</a>'.$this->num_tag_close;
                }

                $result .= $this->cur_tag_open.$i.$this->cur_tag_close;

                if ($this->current_page != $this->total_pages) {
                    if ($this->current_page + 4 > $this->total_pages) {
                        $lastPage = $this->current_page + ($this->total_pages - $this->current_page);
                    } else {
                        $lastPage = $this->current_page + 3;
                    }

                    for ($i = $this->current_page + 1; $i <= $lastPage; ++$i) {
                        $result .= $this->num_tag_open.'<a href="'.$this->base_url.
                            '/'.$i.'">'.$i.'</a>'.$this->num_tag_close;
                    }
                }
            } elseif ($this->total_pages > 4 && $this->current_page < 5) {
                if ($this->current_page + 4 > $this->total_pages) {
                    $lastPage = $this->current_page + ($this->total_pages - $this->current_page);
                } else {
                    $lastPage = $this->current_page + 3;
                }

                for ($i = 1; $i <= $lastPage; ++$i) {
                    if ($i == $this->current_page) {
                        $result .= $this->cur_tag_open.$i.$this->cur_tag_close;
                    } else {
                        $result .= $this->num_tag_open.'<a href="'.$this->base_url.
                            '/'.$i.'">'.$i.'</a>'.$this->num_tag_close;
                    }
                }
            } else {
                for ($i = 1; $i <= $this->total_pages; ++$i) {
                    if ($i == $this->current_page) {
                        $result .= $this->cur_tag_open.$i.$this->cur_tag_close;
                    } else {
                        $result .= $this->num_tag_open.'<a href="'.$this->base_url.
                            '/'.$i.'">'.$i.'</a>'.$this->num_tag_close;
                    }
                }
            }

            if ($this->current_page < $this->total_pages) {
                // Next Page Link
                $result .= $this->next_tag_open.'<a href="'.$this->base_url.
                    '/'.($this->current_page + 1).'">'.$this->next_link.
                    '</a>'.$this->next_tag_close;

                // Last Page Link
                if (false !== $this->last_link) {
                    $result .= $this->last_tag_open.'<a href="'.$this->base_url.
                        '/'.$this->total_pages.'">'.$this->last_link.
                        '</a>'.$this->last_tag_close;
                }
            }

            $result .= $this->main_tag_close;

            return $result;
        }
    }
}
