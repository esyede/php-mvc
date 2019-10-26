<?php

defined('BASE') or exit('No direct script access allowed');

class Migration
{
    protected $enabled = false;
    protected $current = 0;
    protected $db = null;
    protected $schema = null;
    protected $error = '';

    private $table = 'migrations';

    public function __construct()
    {
        $config = config('migration');
        $this->enabled = $config['enabled'];
        $this->table = $config['table'];
        $this->current = $config['current_version'];

        if (true !== $this->enabled) {
            $message = 'Migrations has been loaded but is disabled or set up incorrectly.';
            throw new \Exception($message);
        }

        $load = get_instance();
        $this->db = $load->database();
        $this->schema = $load->schema();

        if (! in_array($this->table, $this->schema->listTables())) {
            $this->createMigrationTable();
        }
    }

    private function createMigrationTable()
    {
        $table = $this->schema->createTable($this->table);
        $table->addColumn('version')->int(3)->nullable(false)->index(true);

        try {
            $table->build();
            $this->db->from($this->table)
            ->insert(['version' => 0])
            ->execute();
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage());
        }
    }

    public function version($target)
    {
        $start = $current = $this->getVersion();
        $stop = $target;

        if ($target > $current) {
            ++$start;
            ++$stop;
            $step = 1;
        } else {
            $step = -1;
        }

        $method = (1 === $step) ? 'up' : 'down';
        $migrations = [];
        for ($i = $start; $i != $stop; $i += $step) {
            $files = glob(sprintf(app_path('migrations/%03d_*.php'), $i));
            if (count($files) > 1) {
                $message = 'There are multiple migrations with the same version number: %s.';
                $this->error = sprintf($message, $i);

                return false;
            }

            if (0 === count($files)) {
                if (1 === $step) {
                    break;
                }

                $message = 'No migration could be found with the version number: %s.';
                $this->error = sprintf($message, $i);

                return false;
            }

            $file = basename($files[0]);
            $name = basename($files[0], '.php');

            if (preg_match('/^\d{3}_(\w+)$/', $name, $match)) {
                $match[1] = strtolower($match[1]);
                if (in_array($match[1], $migrations)) {
                    $message = 'There are multiple migrations with the same version number: %s.';
                    $this->error = sprintf($message, $match[1]);

                    return false;
                }

                include $files[0];
                $class = 'Migration_'.strtolower($match[1]);

                if (! class_exists($class)) {
                    $message = 'The migration class "%s" could not be found.';
                    $this->error = sprintf($message, $class);

                    return false;
                }

                if (! is_callable([$class, $method])) {
                    $message = 'The migration class "%s" is missing an %s() method.';
                    $this->error = sprintf($message, $class, $method);

                    return false;
                }

                $migrations[] = $match[1];
            } else {
                $message = 'Migration "%s" has an invalid filename.';
                $this->error = sprintf($message, $file);

                return false;
            }
        }

        $version = $i + (1 == $step ? -1 : 0);
        if (blank($migrations)) {
            return true;
        }

        foreach ($migrations as $migration) {
            $class = 'Migration_'.strtolower($migration);
            call_user_func([new $class(), $method]);
            $current += $step;
            $this->updateVersion($current);
        }

        return $current;
    }

    public function latest()
    {
        if (! $migrations = $this->listMigrations()) {
            $this->error = 'No migrations were found.';

            return false;
        }

        $last = basename(end($migrations));

        return $this->version((int) substr($last, 0, 3));
    }

    public function current()
    {
        return $this->version($this->current);
    }

    public function errors()
    {
        return $this->error;
    }

    protected function listMigrations()
    {
        $files = glob(app_path('migrations/*_*.php'));
        $count = count($files);

        for ($i = 0; $i < $count; ++$i) {
            $name = basename($files[$i], '.php');
            if (! preg_match('/^\d{3}_(\w+)$/', $name)) {
                $files[$i] = false;
            }
        }

        sort($files);

        return $files;
    }

    protected function getVersion()
    {
        $row = $this->db->from($this->table)->one();

        return filled($row) ? $row['version'] : 0;
    }

    protected function updateVersion($migrations)
    {
        return $this->db
            ->from($this->table)
            ->update(['version' => $migrations])
            ->execute();
    }
}
