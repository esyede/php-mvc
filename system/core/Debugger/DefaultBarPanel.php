<?php

namespace Debugger;

defined('BASE') or exit('No direct script access allowed');

class DefaultBarPanel implements BarPanelInterface
{
    private $id;
    public $data;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getTab()
    {
        ob_start(function () { });

        $data = $this->data;
        require __DIR__.DS.'assets'.DS.'Bar'.DS.$this->id.'.tab.phtml';

        return ob_get_clean();
    }

    public function getPanel()
    {
        ob_start(function () { });

        if (is_file(__DIR__.DS.'assets'.DS.'Bar'.DS.$this->id.'.panel.phtml')) {
            $data = $this->data;
            require __DIR__.DS.'assets'.DS.'Bar'.DS.$this->id.'.panel.phtml';
        }

        return ob_get_clean();
    }
}
