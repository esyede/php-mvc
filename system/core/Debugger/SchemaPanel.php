<?php

namespace Debugger;

defined('BASE') or exit('No direct script access allowed');

class SchemaPanel implements BarPanelInterface
{
    private $schema;

    public function __construct(\Schema $schema)
    {
        $this->schema = $schema;
    }

    public function getPanel()
    {
        ob_start(function () {});
        require __DIR__.DS.'assets'.DS.'Bar'.DS.'schema.panel.phtml';

        return ob_get_clean();
    }

    public function getTab()
    {
        ob_start(function () {});
        require __DIR__.DS.'assets'.DS.'Bar'.DS.'schema.tab.phtml';

        return ob_get_clean();
    }

    public function getQueryCount()
    {
        $count = $this->getQueryStatistics();

        return $count['num_queries'];
    }
}
