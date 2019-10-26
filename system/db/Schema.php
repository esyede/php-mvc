<?php

defined('BASE') or exit('No direct script access allowed');

//!--------------------------------------------------------------------------
//! Main schema class
//!--------------------------------------------------------------------------

class Schema
{
    use SchemaUtilityTrait;

    public $name;
    public $config;
    public $data_types = [
        'BOOLEAN'    => ['mysql' => 'tinyint(1)', 'sqlite|pgsql' => 'BOOLEAN'],
        'INT1'       => ['mysql' => 'tinyint(4)', 'sqlite' => 'integer(4)', 'pgsql' => 'smallint'],
        'INT2'       => ['mysql' => 'smallint(6)', 'sqlite' => 'integer(6)', 'pgsql' => 'smallint'],
        'INT4'       => ['mysql' => 'int(11)', 'sqlite' => 'integer(11)', 'pgsql' => 'integer'],
        'INT8'       => ['mysql' => 'bigint(20)', 'sqlite' => 'integer(20)', 'pgsql' => 'bigint'],
        'FLOAT'      => ['mysql|sqlite' => 'FLOAT', 'pgsql' => 'double precision'],
        'DOUBLE'     => ['mysql' => 'decimal(18,6)', 'sqlite' => 'decimal(15,6)', 'pgsql' => 'numeric(18,6)'],
        'VARCHAR128' => ['mysql|sqlite' => 'varchar(128)', 'pgsql' => 'character varying(128)'],
        'VARCHAR256' => ['mysql|sqlite' => 'varchar(255)', 'pgsql' => 'character varying(255)'],
        'VARCHAR512' => ['mysql|sqlite' => 'varchar(512)', 'pgsql' => 'character varying(512)'],
        'TEXT'       => ['mysql|sqlite|pgsql' => 'text'],
        'LONGTEXT'   => ['mysql' => 'LONGTEXT', 'sqlite|pgsql' => 'text'],
        'DATE'       => ['mysql|sqlite|pgsql' => 'date'],
        'DATETIME'   => ['mysql|sqlite' => 'datetime', 'pgsql' => 'timestamp without time zone'],
        'TIMESTAMP'  => ['mysql' => 'timestamp', 'sqlite' => 'DATETIME', 'pgsql' => 'timestamp without time zone'],
        'BLOB'       => ['mysql|sqlite' => 'blob', 'pgsql' => 'bytea'],
    ];

    public $default_types = [
        'CUR_STAMP' => [
            'mysql'  => 'CURRENT_TIMESTAMP',
            'sqlite' => "(datetime('now','localtime'))",
            'pgsql'  => 'LOCALTIMESTAMP(0)',
            ],
        ];

    public static $strict = false;

    const DT_BOOL = 'BOOLEAN';
    const DT_BOOLEAN = 'BOOLEAN';
    const DT_INT1 = 'INT1';
    const DT_TINYINT = 'INT1';
    const DT_INT2 = 'INT2';
    const DT_SMALLINT = 'INT2';
    const DT_INT4 = 'INT4';
    const DT_INT = 'INT4';
    const DT_INT8 = 'INT8';
    const DT_BIGINT = 'INT8';
    const DT_FLOAT = 'FLOAT';
    const DT_DOUBLE = 'DOUBLE';
    const DT_DECIMAL = 'DOUBLE';
    const DT_VARCHAR128 = 'VARCHAR128';
    const DT_VARCHAR256 = 'VARCHAR256';
    const DT_VARCHAR512 = 'VARCHAR512';
    const DT_TEXT = 'TEXT';
    const DT_LONGTEXT = 'LONGTEXT';
    const DT_DATE = 'DATE';
    const DT_DATETIME = 'DATETIME';
    const DT_TIMESTAMP = 'TIMESTAMP';
    const DT_BLOB = 'BLOB';
    const DT_BINARY = 'BLOB';

    const DF_CURRENT_TIMESTAMP = 'CUR_STAMP';

    public function __construct(array $config)
    {
        // require_once SYSTEM.DS.'core'.DS.'Debugger'.DS.'SchemaPanel.php';
        // \Debugger\Debugger::getBar()->addPanel(new \Debugger\SchemaPanel($this));
        $conn = null;
        $error = null;
        switch ($config['type']) {
            case 'mysql':
            case 'mysqli':
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s',
                    $config['hostname'],
                    isset($config['port']) ? $config['port'] : 3306,
                    $config['database']
                );
                try {
                    $conn = new \PDO($dsn, $config['username'], $config['password']);
                } catch (\PDOException $e) {
                    $error = $e;
                }
                break;

            case 'pgsql':
                $dsn = sprintf(
                    'pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s',
                    $config['hostname'],
                    isset($config['port']) ? $config['port'] : 5432,
                    $config['database'],
                    $config['username'],
                    $config['password']
                );
                try {
                    $conn = new \PDO($dsn);
                } catch (\PDOException $e) {
                    $error = $e;
                }
                break;

            case 'sqlite':
            case 'sqlite3':
                try {
                    $conn = new \PDO('sqlite:/'.$config['database']);
                } catch (\PDOException $e) {
                    $error = $e;
                }
                break;
        }

        if (null === $conn || null !== $error) {
            throw new \PDOException(strtoupper($config['type']).' '.$error->getMessage());
        }

        $this->db = $conn;
        $this->db_name = $config['database'];
    }

    public function listDatabases()
    {
        $cmd = [
            'mysql' => 'SHOW DATABASES',
            'pgsql' => 'SELECT datname FROM pg_catalog.pg_database',
        ];

        $query = $this->findQuery($cmd);
        if (! $query) {
            return false;
        }

        $result = $this->execute($query);
        if (! is_array($result)) {
            return false;
        }

        foreach ($result as &$db) {
            if (is_array($db)) {
                $db = array_shift($db);
            }
        }

        return $result;
    }

    public function listTables()
    {
        $cmd = [
            'mysql'  => ['SHOW TABLES'],
            'sqlite' => ["SELECT name FROM sqlite_master WHERE type='table' AND name!='sqlite_sequence'"],
            'pgsql'  => ["select table_name from information_schema.tables where table_schema = 'public'"],
        ];

        $query = $this->findQuery($cmd);
        if (! $query[0]) {
            return false;
        }

        $tables = $this->execute($query[0]);
        if ($tables && is_array($tables) && count($tables) > 0) {
            foreach ($tables as &$table) {
                $table = array_shift($table);
            }
        }

        return $tables;
    }

    public function createTable($name)
    {
        return new SchemaTableCreator($name, $this);
    }

    public function alterTable($name)
    {
        return new SchemaTableModifier($name, $this);
    }

    public function renameTable($name, $newName, $exec = true)
    {
        $name = $this->quoteKey($name);
        $newName = $this->quoteKey($newName);
        $cmd = [
            'sqlite|pgsql' => "ALTER TABLE $name RENAME TO $newName;",
            'mysql'        => "RENAME TABLE $name TO $newName;",
        ];

        $query = $this->findQuery($cmd);
        if (! $exec) {
            return $query;
        }

        return $this->schema->execute($query);
    }

    public function dropTable($name, $exec = true)
    {
        if (is_object($name) && $name instanceof SchemaTableBuilder) {
            $name = $name->name;
        }

        $cmd = ['mysql|sqlite|pgsql' => 'DROP TABLE IF EXISTS '.$this->quoteKey($name).';'];
        $query = $this->findQuery($cmd);

        return ($exec) ? $this->execute($query) : $query;
    }

    public function truncateTable($name, $exec = true)
    {
        if (is_object($name) && $name instanceof SchemaTableBuilder) {
            $name = $name->name;
        }

        $cmd = [
            'mysql|pgsql' => 'TRUNCATE TABLE '.$this->quoteKey($name).';',
            'sqlite'      => [
                'DELETE FROM '.$this->quoteKey($name).';',
                // 'UPDATE SQLITE_SEQUENCE SET seq = 0 WHERE name = '.$this->quoteKey($name).';',
            ],
        ];
        $query = $this->findQuery($cmd);

        return ($exec) ? $this->schema->execute($query) : $query;
    }

    public function isCompatible($colType, $colDef)
    {
        $rawType = $this->findQuery($this->data_types[strtoupper($colType)]);
        preg_match_all('/(?P<type>\w+)($|\((?P<length>(\d+|(.*)))\))/', $rawType, $match);

        return (bool) preg_match_all('/'.preg_quote($match['type'][0]).'($|\('.
            preg_quote($match['length'][0]).'\))/i', $colDef);
    }

    public function getDriver()
    {
        return $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    public function getSchema($table, $fields = null)
    {
        $dbname = $this->db_name;
        $cmd = [
            'sqlite' => [
                'PRAGMA table_info(`'.$table.'`)',
                'name', 'type', 'dflt_value', 'notnull', 0, 'pk', true, ],
            'mysql' => [
                'SHOW columns FROM `'.$dbname.'`.`'.$table.'`',
                'Field', 'Type', 'Default', 'Null', 'YES', 'Key', 'PRI', ],
            'pgsql' => [
                'SELECT '.
                    'C.COLUMN_NAME AS field,'.
                    'C.DATA_TYPE AS type,'.
                    'C.COLUMN_DEFAULT AS defval,'.
                    'C.IS_NULLABLE AS nullable,'.
                    'T.CONSTRAINT_TYPE AS pkey '.
                'FROM INFORMATION_SCHEMA.COLUMNS AS C '.
                'LEFT OUTER JOIN '.
                    'INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K '.
                    'ON '.
                        'C.TABLE_NAME=K.TABLE_NAME AND '.
                        'C.COLUMN_NAME=K.COLUMN_NAME AND '.
                        'C.TABLE_SCHEMA=K.TABLE_SCHEMA '.
                        ($dbname ? ('AND C.TABLE_CATALOG=K.TABLE_CATALOG ') : '').
                'LEFT OUTER JOIN '.
                    'INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON '.
                        'K.TABLE_NAME=T.TABLE_NAME AND '.
                        'K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND '.
                        'K.TABLE_SCHEMA=T.TABLE_SCHEMA '.
                        ($dbname ? ('AND K.TABLE_CATALOG=T.TABLE_CATALOG ') : '').
                'WHERE '.
                    'C.TABLE_NAME='.$this->db->quote($table).
                    ($dbname ? (' AND C.TABLE_CATALOG='.$this->db->quote($dbname)) : ''),
                'field', 'type', 'defval', 'nullable', 'YES', 'pkey', 'PRIMARY KEY', ],
        ];
        $conv = [
            'int\b|integer'                     => \PDO::PARAM_INT,
            'bool'                              => \PDO::PARAM_BOOL,
            'blob|bytea|image|binary'           => \PDO::PARAM_LOB,
            'float|real|double|decimal|numeric' => 'float',
            '.+'                                => \PDO::PARAM_STR,
        ];

        $driver = $this->getDriver();
        foreach ($cmd as $key => $val) {
            if (preg_match('/'.$key.'/', $driver)) {
                $rows = [];
                $exec = $this->execute($val[0]);
                foreach ($exec as $row) {
                    if (! $fields || in_array($row[$val[1]], $fields)) {
                        foreach ($conv as $regex => $type) {
                            if (preg_match('/'.$regex.'/i', $row[$val[2]])) {
                                break;
                            }
                        }

                        $rows[$row[$val[1]]] = [
                            'type'     => $row[$val[2]],
                            'pdo_type' => $type,
                            'default'  => is_string($row[$val[3]])
                                ? preg_replace('/^\s*([\'"])(.*)\1\s*/', '\2', $row[$val[3]])
                                : $row[$val[3]],
                            'nullable' => ($row[$val[4]] == $val[5]),
                            'pkey'     => ($row[$val[6]] == $val[7]),
                        ];
                    }
                }

                return $rows;
            }
        }
        user_error(sprintf("Table '%s' does not have a primary key", $table), E_USER_ERROR);

        return false;
    }

    public function execute($queries)
    {
        if (is_array($queries)) {
            $queries = implode(' ', $queries);
        }
        $stmt = $this->db->prepare($queries);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }
}

//!--------------------------------------------------------------------------
//! Table builder class
//!--------------------------------------------------------------------------
abstract class SchemaTableBuilder
{
    use SchemaUtilityTrait;

    protected $columns;
    protected $pkeys;
    protected $queries;
    protected $primary_key;
    protected $rebuild_command;

    public $name;
    public $schema;

    public function __construct($name, \Schema $schema)
    {
        $this->name = $name;
        $this->schema = $schema;
        $this->columns = [];
        $this->queries = [];
        $this->pkeys = ['id'];
        $this->primary_key = 'id';
        $this->db = $schema->db;
    }

    abstract public function build($exec = true);

    public function addColumn($key, $args = null)
    {
        if ($key instanceof Column) {
            $args = $key->getColumnArray();
            $key = $key->name;
        }
        if (array_key_exists($key, $this->columns)) {
            $message = sprintf('Cannot add the column `%s`. It already exists.', $key);
            throw new \Exception($message);
        }

        $column = new Column($key, $this);

        if ($args) {
            foreach ($args as $arg => $val) {
                $column->{$arg} = $val;
            }
        }

        if (1 == count($this->pkeys) && in_array($key, $this->pkeys)) {
            return $column;
        }

        return $this->columns[$key] = &$column;
    }

    protected function _addIndex($indexCols, $searchCols, $unique, $length)
    {
        if (! is_array($indexCols)) {
            $indexCols = [$indexCols];
        }

        $quotedCols = array_map([$this, 'quoteKey'], $indexCols);
        if (preg_match('/mysql/', $this->schema->getDriver())) {
            foreach ($quotedCols as $i => &$col) {
                if ('TEXT' == strtoupper($searchCols[$indexCols[$i]]['type'])) {
                    $col .= '('.$length.')';
                }
            }
        }

        $cols = implode(',', $quotedCols);
        $name = $this->assembleIndexKey($indexCols, $this->name);
        $name = $this->quoteKey($name);
        $table = $this->quoteKey($this->name);
        $index = $unique ? 'UNIQUE INDEX' : 'INDEX';

        $cmd = [
            'pgsql|sqlite' => "CREATE $index $name ON $table ($cols);",
            'mysql'        => "ALTER TABLE $table ADD $index $name ($cols);",
        ];

        $query = $this->findQuery($cmd);
        $this->queries[] = $query;
    }

    protected function assembleIndexKey($indexCols, $table_name)
    {
        if (! is_array($indexCols)) {
            $indexCols = [$indexCols];
        }

        $name = $table_name.'___'.implode('__', $indexCols);
        if (strlen($name) > 64) {
            $name = $table_name.'___'.$this->hash(implode('__', $indexCols));
        }

        if (strlen($name) > 64) {
            $name = '___'.$this->hash($table_name.'___'.implode('__', $indexCols));
        }

        return $name;
    }

    public function primary($pkeys)
    {
        if (empty($pkeys)) {
            return false;
        }

        if (! is_array($pkeys)) {
            $pkeys = [$pkeys];
        }

        $this->primary_key = $pkeys[0];
        $this->pkeys = $pkeys;

        if (array_key_exists($this->primary_key, $this->columns)) {
            unset($this->columns[$this->primary_key]);
        }

        foreach ($pkeys as $name) {
            if (array_key_exists($name, $this->columns)) {
                $this->columns[$name]->pkey = true;
            }
        }

        if (count($pkeys) > 1) {
            $pkeys_quoted = array_map([$this, 'quoteKey'], $pkeys);
            $pkString = implode(', ', $pkeys_quoted);
            if (preg_match('/sqlite/', $this->schema->getDriver())) {
                $this->rebuild_command['pkeys'] = $pkeys;

                return;
            } else {
                $table = $this->quoteKey($this->name);
                $table_key = $this->quoteKey($this->name.'_pkey');
                $cmd = [
                    'mysql' => "ALTER TABLE $table DROP PRIMARY KEY, ADD PRIMARY KEY ( $pkString );",
                    'pgsql' => [
                        "ALTER TABLE $table DROP CONSTRAINT $table_key;",
                        "ALTER TABLE $table ADD CONSTRAINT $table_key PRIMARY KEY ( $pkString );",
                    ],
                ];

                $query = $this->findQuery($cmd);
                if (! is_array($query)) {
                    $query = [$query];
                }

                foreach ($query as $q) {
                    $this->queries[] = $q;
                }
            }
        }
    }
}

//!--------------------------------------------------------------------------
//! Table creator class
//!--------------------------------------------------------------------------
class SchemaTableCreator extends SchemaTableBuilder
{
    protected $charset = 'utf8mb4';
    protected $collation = 'utf8mb4_unicode_ci';
    protected $engine = 'InnoDB';

    public function charset($charset, $collation)
    {
        $charset = empty($charset) ? 'utf8mb4' : $charset;
        $collation = empty($collation) ? 'utf8mb4_unicode_ci' : $collation;
        $this->charset = $charset;
        $this->collation = $collation;
    }

    public function engine($str)
    {
        $this->engine = $str;
    }

    public function build($exec = true)
    {
        $tables = $this->schema->listTables();
        if ($exec && in_array($this->name, $tables)) {
            $message = sprintf('Table `%s` already exists. Cannot re-create it.', $this->name);
            throw new \Exception($message);
        }

        $cols = '';

        if (filled($this->columns)) {
            foreach ($this->columns as $cname => $column) {
                if (false !== $column->default && is_int(strpos(strtoupper($column->type), 'TEXT'))) {
                    $message = sprintf(
                        "Column `%s` of type TEXT can't have a default value.",
                        $column->name
                    );
                    throw new \Exception($message);
                }
                $cols .= ', '.$column->getColumnQuery();
            }
        }

        $table = $this->quoteKey($this->name);
        $id = $this->quoteKey($this->primary_key);
        $cmd = [
            'sqlite' => "CREATE TABLE $table ($id INTEGER NOT NULL ".
                'PRIMARY KEY AUTOINCREMENT'.$cols.');',
            'mysql' => "CREATE TABLE $table ($id INTEGER UNSIGNED NOT NULL ".
                    'PRIMARY KEY AUTO_INCREMENT'.$cols.') '.
                    "ENGINE=$this->engine DEFAULT CHARSET=$this->charset ".
                    'COLLATE '.$this->collation.';',
            'pgsql' => "CREATE TABLE $table ($id SERIAL PRIMARY KEY".$cols.');',
        ];

        $query = $this->findQuery($cmd);
        if (count($this->pkeys) > 1 && preg_match('/sqlite/', $this->schema->getDriver())) {
            $pkString = implode(', ', $this->pkeys);
            $query = "CREATE TABLE $table ($id INTEGER NULL".$cols.", PRIMARY KEY ($pkString) );";
            $newTable = new SchemaTableModifier($this->name, $this->schema);
            $pk_queries = $newTable->_sqlite_increment_trigger($this->primary_key);
            $this->queries = array_merge($this->queries, $pk_queries);
        }

        array_unshift($this->queries, $query);
        foreach ($this->columns as $cname => $column) {
            if ($column->index) {
                $this->addIndex($cname, $column->unique);
            }
        }

        if (! $exec) {
            return $this->queries;
        }
        $this->schema->execute($this->queries);

        return isset($newTable) ? $newTable : new SchemaTableModifier($this->name, $this->schema);
    }

    public function addIndex($columns, $unique = false, $length = 20)
    {
        if (! is_array($columns)) {
            $columns = [$columns];
        }

        $cols = $this->columns;
        foreach ($cols as &$col) {
            $col = $col->getColumnArray();
        }

        parent::_addIndex($columns, $cols, $unique, $length);
    }
}

//!--------------------------------------------------------------------------
//! Table modifier class
//!--------------------------------------------------------------------------
class SchemaTableModifier extends SchemaTableBuilder
{
    protected $colTypes;
    protected $rebuild_command;

    public function build($exec = true)
    {
        if (! in_array($this->name, $this->schema->listTables())) {
            $message = sprintf('Unable to alter table `%s`. It does not exist.', $this->name);
            throw new \Exception($message);
        }

        if ($sqlite = preg_match('/sqlite/', $this->schema->getDriver())) {
            $sqlite_queries = [];
        }
        $rebuild = false;
        $additional_queries = $this->queries;
        $this->queries = [];

        foreach ($this->columns as $cname => $column) {
            if (false === $column->default && false === $column->nullable) {
                $message = sprintf(
                    'You cannot add the not nullable column `%s` without specifying a default value',
                    $column->name
                );
                throw new \Exception($message);
            }

            if (false !== $column->default && is_int(strpos(strtoupper($column->type), 'TEXT'))) {
                $message = sprintf(
                    "Column `%s` of type TEXT can't have a default value.",
                    $column->name
                );
                throw new \Exception($message);
            }

            $table = $this->quoteKey($this->name);
            $col_query = $column->getColumnQuery();

            if ($sqlite) {
                if (Schema::DF_CURRENT_TIMESTAMP === $column->default) {
                    $rebuild = true;
                    break;
                } else {
                    $sqlite_queries[] = "ALTER TABLE $table ADD $col_query;";
                }
            } else {
                $cmd = ['mysql|pgsql' => "ALTER TABLE $table ADD $col_query;"];
                $this->queries[] = $this->findQuery($cmd);
            }
        }
        if ($sqlite) {
            if ($rebuild || filled($this->rebuild_command)) {
                $this->_sqlite_rebuild($exec);
            } else {
                $this->queries += $sqlite_queries;
            }
        }

        $this->queries = array_merge($this->queries, $additional_queries);

        foreach ($this->columns as $cname => $column) {
            if ($column->index) {
                $this->addIndex($cname, $column->unique);
            }
        }

        if (empty($this->queries)) {
            return false;
        }

        if (is_array($this->queries) && 1 == count($this->queries)) {
            $this->queries = $this->queries[0];
        }

        if (! $exec) {
            return $this->queries;
        }

        $result = $this->schema->execute($this->queries);

        $this->queries = $this->columns = $this->rebuild_command = [];

        return $result;
    }

    protected function _sqlite_rebuild($exec = true)
    {
        $new_columns = $this->columns;
        $existingColumns = $this->listColumns(true);

        $after = [];
        foreach ($new_columns as $cname => $column) {
            if (filled($column->after)) {
                $after[$column->after][] = $cname;
            }
        }

        $rename = (filled($this->rebuild_command) && array_key_exists('rename', $this->rebuild_command))
            ? $this->rebuild_command['rename'] : [];

        foreach ($existingColumns as $key => $col) {
            if ($col['pkey']) {
                $pkeys[array_key_exists($key, $rename) ? $rename[$key] : $key] = $col;
            }
        }

        foreach ($new_columns as $key => $col) {
            if ($col->pkey) {
                $pkeys[$key] = $col;
            }
        }

        $indexes = $this->listIndexes();
        if (filled($this->rebuild_command) && array_key_exists('drop', $this->rebuild_command)) {
            foreach ($this->rebuild_command['drop'] as $name) {
                if (array_key_exists($name, $existingColumns)) {
                    if (array_key_exists($name, $pkeys)) {
                        unset($pkeys[$name]);

                        if (1 == count($pkeys)) {
                            $incrementTrigger = $this->quoteKey($this->name.'_insert');
                            $this->queries[] = 'DROP TRIGGER IF EXISTS '.$incrementTrigger;
                        }
                    }

                    unset($existingColumns[$name]);
                    foreach (array_keys($indexes) as $col) {
                        if ($col == $this->name.'___'.$name) {
                            unset($indexes[$this->name.'___'.$name]);
                        }

                        if (is_int(strpos($col, '__'))) {
                            if (is_int(strpos($col, '___'))) {
                                $col = explode('___', $col);
                                $ci = explode('__', $col[1]);
                                $col = implode('___', $col);
                                if (in_array($name, $ci)) {
                                    unset($indexes[$col]);
                                }
                            }
                        }
                    }
                }
            }
        }

        $oname = $this->name;
        $this->queries[] = $this->rename($oname.'_temp', false);
        $newTable = $this->schema->createTable($oname);

        foreach ($existingColumns as $name => $col) {
            $colName = array_key_exists($name, $rename) ? $rename[$name] : $name;

            if (array_key_exists('update', $this->rebuild_command)
            && in_array($name, array_keys($this->rebuild_command['update']))) {
                $cdat = $this->rebuild_command['update'][$name];

                if ($cdat instanceof Column) {
                    $col = $cdat->getColumnArray();
                } else {
                    $col['type'] = $cdat;
                }
            }

            $newTable->addColumn($colName, $col)->forceDataType();

            if (array_key_exists($name, $after)) {
                foreach (array_reverse($after[$name]) as $acol) {
                    $newTable->addColumn($new_columns[$acol]);
                    unset($new_columns[$acol]);
                }
            }
        }

        foreach ($new_columns as $ncol) {
            $newTable->addColumn($ncol);
        }

        $newTable->primary(array_keys($pkeys));
        foreach (array_reverse($indexes) as $name => $conf) {
            if (is_int(strpos($name, '___'))) {
                list($tname, $name) = explode('___', $name);
            }

            if (is_int(strpos($name, '__'))) {
                $name = explode('__', $name);
            }

            if ($exec) {
                $t = $this->schema->alterTable($oname);
                $t->dropIndex($name);
                $t->build();
            }

            $newTable->addIndex($name, $conf['unique']);
        }

        $newTableQueries = $newTable->build(false);
        $this->queries = array_merge($this->queries, $newTableQueries);

        if (filled($existingColumns)) {
            foreach (array_keys($existingColumns) as $name) {
                $fields_from[] = $this->quoteKey($name);
                $toName = array_key_exists($name, $rename) ? $rename[$name] : $name;
                $fields_to[] = $this->quoteKey($toName);
            }
            $this->queries[] =
                'INSERT INTO '.$this->quoteKey($newTable->name).' ('.implode(', ', $fields_to).') '.
                'SELECT '.implode(', ', $fields_from).' FROM '.$this->quoteKey($this->name).';';
        }
        $this->queries[] = $this->drop(false);
        $this->name = $oname;
    }

    public function _sqlite_increment_trigger($pkey)
    {
        $table = $this->quoteKey($this->name);
        $pkey = $this->quoteKey($pkey);
        $triggerName = $this->quoteKey($this->name.'_insert');

        $queries[] = "DROP TRIGGER IF EXISTS $triggerName;";
        $queries[] = 'CREATE TRIGGER '.$triggerName.' AFTER INSERT ON '.$table.
            ' WHEN (NEW.'.$pkey.' IS NULL) BEGIN'.
            ' UPDATE '.$table.' SET '.$pkey.' = ('.
            ' select coalesce( max( '.$pkey.' ), 0 ) + 1 from '.$table.
            ') WHERE ROWID = NEW.ROWID;'.
            ' END;';

        return $queries;
    }

    public function listColumns($types = false)
    {
        $schema = $this->schema->getSchema($this->name, null, 0);
        if (! $types) {
            return array_keys($schema);
        } else {
            foreach ($schema as $name => &$cols) {
                $default = ('' === $cols['default']) ? null : $cols['default'];
                if (! is_null($default) && ((is_int(strpos(
                    $curdef = strtolower(
                        $this->findQuery($this->schema->default_types['CUR_STAMP'])
                    ),
                    strtolower($default)
                ))
                || is_int(strpos(strtolower($default), $curdef)))
                || "('now'::text)::timestamp(0) without time zone" == $default)) {
                    $default = 'CUR_STAMP';
                } elseif (! is_null($default)) {
                    if (preg_match('/sqlite/', $this->schema->getDriver())) {
                        $default = preg_replace('/^\s*([\'"])(.*)\1\s*$/', '\2', $default);
                    } elseif (preg_match('/pgsql/', $this->schema->getDriver())) {
                        if (is_int(strpos($default, 'nextval'))) {
                            $default = null;
                        } elseif (preg_match("/^\'*(.*)\'*::(\s*\w)+/", $default, $match)) {
                            $default = $match[1];
                        }
                    }
                } else {
                    $default = false;
                }

                $cols['default'] = $default;
            }
        }

        return $schema;
    }

    public function isCompatible($colType, $column)
    {
        $cols = $this->listColumns(true);

        return $this->schema->isCompatible($colType, $cols[$column]['type']);
    }

    public function dropColumn($name)
    {
        $colTypes = $this->listColumns(true);
        if (! in_array($name, array_keys($colTypes))) {
            return true;
        }

        if (preg_match('/sqlite/', $this->schema->getDriver())) {
            $this->rebuild_command['drop'][] = $name;
        } else {
            $quotedTable = $this->quoteKey($this->name);
            $quotedColumn = $this->quoteKey($name);
            $cmd = [
                'mysql' => "ALTER TABLE $quotedTable DROP $quotedColumn;",
                'pgsql' => "ALTER TABLE $quotedTable DROP COLUMN $quotedColumn;",
            ];

            $this->queries[] = $this->findQuery($cmd);
        }
    }

    public function renameColumn($name, $newName)
    {
        $existingColumns = $this->listColumns(true);
        if (! in_array($name, array_keys($existingColumns))) {
            trigger_error('Cannot rename column. it does not exist.', E_USER_ERROR);
        }

        if (in_array($newName, array_keys($existingColumns))) {
            trigger_error('Cannot rename column. new column already exist.', E_USER_ERROR);
        }

        if (preg_match('/sqlite/', $this->schema->getDriver())) {
            $this->rebuild_command['rename'][$name] = $newName;
        } else {
            $existingColumns = $this->listColumns(true);
            $quotedTable = $this->quoteKey($this->name);
            $quotedColumn = $this->quoteKey($name);
            $quotedColumnNew = $this->quoteKey($newName);

            $cmd = [
                'mysql' => "ALTER TABLE $quotedTable CHANGE $quotedColumn $quotedColumnNew ".
                        $existingColumns[$name]['type'].';',
                'pgsql' => "ALTER TABLE $quotedTable RENAME COLUMN $quotedColumn TO $quotedColumnNew;",
            ];

            $this->queries[] = $this->findQuery($cmd);
        }
    }

    public function modifyColumn($name, $dataType, $force = false)
    {
        if ($dataType instanceof Column) {
            $col = $dataType;
            $dataType = $col->type;
            $force = $col->force;
        }

        if (! $force) {
            $dataType = $this->findQuery($this->schema->data_types[strtoupper($dataType)]);
        }

        $table = $this->quoteKey($this->name);
        $column = $this->quoteKey($name);
        if (preg_match('/sqlite/', $this->schema->getDriver())) {
            $this->rebuild_command['update'][$name] = isset($col) ? $col : $dataType;
        } else {
            $dat = isset($col) ? $col->getColumnQuery() : $column.' '.$dataType;
            $cmd = [
                'mysql' => "ALTER TABLE $table MODIFY COLUMN $dat;",
                'pgsql' => "ALTER TABLE $table ALTER COLUMN $column TYPE $dataType;",
            ];

            if (isset($col)) {
                $cmd['pgsql'] = [$cmd['pgsql']];
                $cmd['pgsql'][] = "ALTER TABLE $table ALTER COLUMN $column SET DEFAULT ".
                    $col->getDefault().';';

                if ($col->nullable) {
                    $cmd['pgsql'][] = "ALTER TABLE $table ALTER COLUMN $column DROP NOT NULL;";
                } else {
                    $cmd['pgsql'][] = "ALTER TABLE $table ALTER COLUMN $column SET NOT NULL;";
                }
            }
            $this->queries[] = $this->findQuery($cmd);
        }
    }

    public function addIndex($columns, $unique = false, $length = 20)
    {
        if (! is_array($columns)) {
            $columns = [$columns];
        }

        $existingCol = $this->columns;
        foreach ($existingCol as &$col) {
            $col = $col->getColumnArray();
        }

        $allCols = array_merge($this->listColumns(true), $existingCol);
        parent::_addIndex($columns, $allCols, $unique, $length);
    }

    public function dropIndex($name)
    {
        if (is_array($name)) {
            $name = $this->name.'___'.implode('__', $name);
        } elseif (! is_int(strpos($name, '___'))) {
            $name = $this->name.'___'.$name;
        }

        $name = $this->quoteKey($name);
        $table = $this->quoteKey($this->name);
        $cmd = [
            'pgsql|sqlite' => "DROP INDEX $name;",
            'mysql'        => "ALTER TABLE $table DROP INDEX $name;",
        ];

        $query = $this->findQuery($cmd);
        $this->queries[] = $query;
    }

    public function listIndexes()
    {
        $table = $this->quoteKey($this->name);
        $cmd = [
            'sqlite' => "PRAGMA index_list($table);",
            'mysql'  => "SHOW INDEX FROM $table;",
            'pgsql'  => 'select i.relname as name, ix.indisunique as unique '.
                'from pg_class t, pg_class i, pg_index ix '.
                'where t.oid = ix.indrelid and i.oid = ix.indexrelid '.
                "and t.relkind = 'r' and t.relname = '$this->name'".
                'group by t.relname, i.relname, ix.indisunique;',
        ];

        $result = $this->schema->execute($this->findQuery($cmd));

        $indexes = [];
        if (preg_match('/pgsql|sqlite/', $this->schema->getDriver())) {
            foreach ($result as $row) {
                $indexes[$row['name']] = ['unique' => $row['unique']];
            }
        } elseif (preg_match('/mysql/', $this->schema->getDriver())) {
            foreach ($result as $row) {
                $indexes[$row['Key_name']] = ['unique' => ! (bool) $row['Non_unique']];
            }
        } else {
            $message = sprintf(
                'DB Engine `%s` is not supported for this action.',
                $this->schema->getDriver()
            );
            throw new \Exception($message);
        }

        return $indexes;
    }

    public function rename($newName, $exec = true)
    {
        $query = $this->schema->renameTable($this->name, $newName, $exec);
        $this->name = $newName;

        return ($exec) ? $this : $query;
    }

    public function drop($exec = true)
    {
        return $this->dropTable($this, $exec);
    }
}

//!--------------------------------------------------------------------------
//! Column definition class
//!--------------------------------------------------------------------------
class Column
{
    use SchemaUtilityTrait;

    public $name;
    public $type;
    public $nullable;
    public $default;
    public $after;
    public $index;
    public $unique;
    public $force;
    public $pkey;

    protected $table;
    protected $schema;
    protected $type_value;

    public function __construct($name, SchemaTableBuilder $table)
    {
        $this->name = $name;
        $this->nullable = true;
        $this->default = false;
        $this->after = false;
        $this->index = false;
        $this->unique = false;
        $this->force = false;
        $this->pkey = false;

        $this->table = $table;
        $this->schema = $table->schema;
        $this->db = $this->schema->db;
    }

    public function type($dataType, $force = false)
    {
        $this->type = $dataType;
        $this->force = $force;

        return $this;
    }

    public function tinyint()
    {
        $this->type = Schema::DT_INT1;

        return $this;
    }

    public function smallint()
    {
        $this->type = Schema::DT_INT2;

        return $this;
    }

    public function int()
    {
        $this->type = Schema::DT_INT4;

        return $this;
    }

    public function bigint()
    {
        $this->type = Schema::DT_INT8;

        return $this;
    }

    public function float()
    {
        $this->type = Schema::DT_FLOAT;

        return $this;
    }

    public function decimal()
    {
        $this->type = Schema::DT_DOUBLE;

        return $this;
    }

    public function double()
    {
        $this->type = Schema::DT_DOUBLE;

        return $this;
    }

    public function text()
    {
        $this->type = Schema::DT_TEXT;

        return $this;
    }

    public function longtext()
    {
        $this->type = Schema::DT_LONGTEXT;

        return $this;
    }

    public function varchar($length = 255)
    {
        $this->type = "varchar($length)";
        $this->force = true;

        return $this;
    }

    public function date()
    {
        $this->type = Schema::DT_DATE;

        return $this;
    }

    public function datetime()
    {
        $this->type = Schema::DT_DATETIME;

        return $this;
    }

    public function timestamp($asDefault = false)
    {
        $this->type = Schema::DT_TIMESTAMP;
        if ($asDefault) {
            $this->default = Schema::DF_CURRENT_TIMESTAMP;
        }

        return $this;
    }

    public function blob()
    {
        $this->type = Schema::DT_BLOB;

        return $this;
    }

    public function boolean()
    {
        $this->type = Schema::DT_BOOLEAN;

        return $this;
    }

    public function forceDataType($state = true)
    {
        $this->force = $state;

        return $this;
    }

    public function nullable($state = true)
    {
        $this->nullable = $state;

        return $this;
    }

    public function defaults($default)
    {
        $this->default = $default;

        return $this;
    }

    public function after($name)
    {
        $this->after = $name;

        return $this;
    }

    public function index($unique = false)
    {
        $this->index = true;
        $this->unique = $unique;

        return $this;
    }

    public function getColumnArray()
    {
        $fields = [
            'name', 'type', 'force', 'default',
            'nullable', 'index', 'unique', 'after', 'pkey',
        ];

        $fields = array_flip($fields);
        foreach ($fields as $key => &$val) {
            $val = $this->{$key};
        }

        unset($val);

        return $fields;
    }

    public function getTypeValue()
    {
        if (! $this->type) {
            $message = sprintf(
                'Cannot build a column query for `%s`: no column type set',
                $this->name
            );
            trigger_error($message, E_USER_ERROR);
        }

        if ($this->force) {
            $this->type_value = $this->type;
        } else {
            $this->type_value = $this->findQuery($this->schema->data_types[strtoupper($this->type)]);
            if (! $this->type_value) {
                if (Schema::$strict) {
                    $message = sprintf(
                        'The specified datatype %s is not defined in %s driver. '.
                        'Use the Column::forceDataType() method if you want to enforce this datatype.',
                        strtoupper($this->type),
                        $this->schema->getDriver()
                    );

                    throw new \Exception($message);
                } else {
                    $this->type_value = $this->type;
                }
            }
        }

        return $this->type_value;
    }

    public function getColumnQuery()
    {
        $typeValue = $this->getTypeValue();
        $query = $this->quoteKey($this->name).' '.$typeValue.' '.$this->getNullable();
        if (preg_match('/bool/i', $typeValue) && null !== $this->default) {
            $this->default = (int) $this->default;
        }

        if (false !== $this->default) {
            $def_cmds = ['sqlite|mysql|pgsql' => 'DEFAULT'];
            $def_cmd = $this->findQuery($def_cmds).' '.$this->getDefault();
            $query .= ' '.$def_cmd;
        }

        if (filled($this->after) && $this->table instanceof SchemaTableModifier) {
            if (preg_match('/mysql/', $this->schema->getDriver())) {
                $after_cmd = 'AFTER '.$this->quoteKey($this->after);
                $query .= ' '.$after_cmd;
            }
        }

        return $query;
    }

    public function getNullable()
    {
        return $this->nullable ? 'NULL' : 'NOT NULL';
    }

    public function getDefault()
    {
        if (Schema::DF_CURRENT_TIMESTAMP === $this->default) {
            $stampType = $this->findQuery($this->schema->data_types['TIMESTAMP']);
            if ('TIMESTAMP' != $this->type
            && ($this->force && strtoupper($this->type) != strtoupper($stampType))) {
                $message = 'Current timestamp as column default is '.
                    'only possible for TIMESTAMP datatype';
                trigger_error($message, E_USER_ERROR);
            }

            return $this->findQuery($this->schema->default_types[strtoupper($this->default)]);
        } else {
            $typeValue = $this->getTypeValue();
            $pdo_type = preg_match('/int|bool/i', $typeValue, $parts)
                ? constant('\PDO::PARAM_'.strtoupper($parts[0]))
                : \PDO::PARAM_STR;

            return null === $this->default
                ? 'NULL'
                : $this->db->quote(
                    htmlspecialchars(
                        $this->default,
                        ENT_QUOTES,
                        'UTF-8'
                    ),
                    $pdo_type
                );
        }
    }
}

//!--------------------------------------------------------------------------
//! Schema utility class
//!--------------------------------------------------------------------------
trait SchemaUtilityTrait
{
    public $db;

    public function findQuery($cmd)
    {
        $driver = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        // dd($driver);
        foreach ($cmd as $backend => $val) {
            if (preg_match('/'.$backend.'/', $driver)) {
                return $val;
            }
        }

        $message = sprintf(
            'DB Engine `%s` is not supported for this action.',
            $driver
        );

        throw new \Exception($message);
    }

    public function quoteKey($key, $split = true)
    {
        $driver = $this->db->getattribute(\PDO::ATTR_DRIVER_NAME);
        $delims = ['sqlite|mysql' => '``', 'pgsql' => '""'];
        $use = '';
        foreach ($delims as $engine => $delim) {
            if (preg_match('/'.$engine.'/', $driver)) {
                $use = $delim;
                break;
            }
        }

        return $use[0].($split ? implode($use[1].'.'.$use[0], explode('.', $key)) : $key).$use[1];
    }

    public function hash($str)
    {
        return str_pad(base_convert(substr(sha1($str), -16), 16, 36), 11, '0', STR_PAD_LEFT);
    }
}
