<?php

namespace Debugger;

defined('BASE') or exit('No direct script access allowed');

class DbPanel implements BarPanelInterface
{
    private $db;
    private $connection;

    public function __construct(\Database $db)
    {
        $this->db = $db;
        $this->connection = $this->db->getConnection();
        $this->db->stats_enabled = true;
    }

    public function getPanel()
    {
        ob_start(function () { });
        require __DIR__.DS.'assets'.DS.'Bar'.DS.'db.panel.phtml';

        return ob_get_clean();
    }

    public function getTab()
    {
        ob_start(function () { });
        require __DIR__.DS.'assets'.DS.'Bar'.DS.'db.tab.phtml';

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
        return $this->db->getStats();
    }

    public function getDbServerVersion()
    {
        $pdo = $this->connection;

        return $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    public function getDbClientVersion()
    {
        $pdo = $this->connection;

        return $pdo->getAttribute(\PDO::ATTR_CLIENT_VERSION);
    }

    public function getDbConnectionStatus()
    {
        $pdo = $this->connection;

        return $pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
    }

    public function getDbPresistenMode()
    {
        $pdo = $this->connection;

        return $pdo->getAttribute(\PDO::ATTR_PERSISTENT);
    }

    public function getDbServerInfo()
    {
        $pdo = $this->connection;

        return $pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
    }
}
