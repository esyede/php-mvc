<?php

namespace Debugger;

defined('BASE') or exit('No direct script access allowed');

class GlobalsPanel implements BarPanelInterface
{
    public function __construct()
    {
    }

    public function getPanel()
    {
        ob_start(function () {
        });
        require __DIR__.DS.'assets'.DS.'Bar'.DS.'globals.panel.php';

        return ob_get_clean();
    }

    public function getTab()
    {
        ob_start(function () {
        });
        require __DIR__.DS.'assets'.DS.'Bar'.DS.'globals.tab.php';

        return ob_get_clean();
    }
}
