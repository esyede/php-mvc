<?php

namespace Debugger;

defined('BASE') or exit('No direct script access allowed');

class DatabasePanel implements BarPanelInterface
{
    private $database;

    public function __construct(\Database $database)
    {
        $this->database = $database;
        $this->database->stats_enabled = true;
    }

    public function getPanel()
    {
        ob_start(function () {
        });
        require __DIR__.DS.'assets'.DS.'Bar'.DS.'database.panel.phtml';

        return ob_get_clean();
    }

    public function getTab()
    {
        ob_start(function () {
        });
        require __DIR__.DS.'assets'.DS.'Bar'.DS.'database.tab.phtml';

        return ob_get_clean();
    }

    public function getQueryCount()
    {
        $count = $this->getQueryStatistics();

        return $count['num_queries'];
    }

    public function getTotalQueryTime()
    {
        $time = $this->getQueryStatistics();

        return $time['total_time'];
    }

    public function getQueryStatistics()
    {
        return $this->database->getStats();
    }

    public function getDbServerVersion()
    {
        $pdo = $this->database->getDb();

        return $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    public function getDbClientVersion()
    {
        $pdo = $this->database->getDb();

        return $pdo->getAttribute(\PDO::ATTR_CLIENT_VERSION);
    }

    public function getDbConnectionStatus()
    {
        $pdo = $this->database->getDb();

        return $pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
    }

    public function getDbPresistenMode()
    {
        $pdo = $this->database->getDb();

        return $pdo->getAttribute(\PDO::ATTR_PERSISTENT);
    }

    public function getDbServerInfo()
    {
        $pdo = $this->database->getDb();

        return $pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
    }
}
