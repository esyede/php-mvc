<?php

defined('BASE') or exit('No direct script access allowed');

use Debugger\DbPanel;
use Debugger\Debugger;

class Database
{
    protected $db;
    protected $table;
    protected $where;
    protected $joins;
    protected $order;
    protected $groups;
    protected $having;
    protected $distinct;
    protected $limit;
    protected $offset;
    protected $sql;

    protected $cache;
    protected $cache_type;
    protected $stats;
    protected $query_time;

    private static $instance;

    public $last_query;
    public $num_rows;
    public $insert_id;
    public $affected_rows;
    public $is_cached = false;
    public $stats_enabled = false;
    public $show_sql = false;
    public $key_prefix = '';

    public function __construct(array $configs = [])
    {
        $this->connect($configs);
        Debugger::getBar()->addPanel(new DbPanel($this));

        return $this->db;
    }

    public static function init(array $configs = [])
    {
        if (null === static::$instance) {
            static::$instance = new static($configs);
        }
        
        return self::$instance;
    }

    //!--------------------------------------------------------------------------
    //! Core methods
    //!--------------------------------------------------------------------------

    public function build($sql, $input)
    {
        return (strlen($input) > 0) ? ($sql.' '.$input) : $sql;
    }

    public function getStats()
    {
        $this->stats['total_time'] = 0;
        $this->stats['num_queries'] = 0;
        $this->stats['num_rows'] = 0;
        $this->stats['num_changes'] = 0;

        if (isset($this->stats['queries'])) {
            foreach ($this->stats['queries'] as $query) {
                $this->stats['total_time'] += $query['time'];
                ++$this->stats['num_queries'];
                $this->stats['num_rows'] += $query['rows'];
                $this->stats['num_changes'] += $query['changes'];
            }
        }

        $this->stats['avg_query_time'] =
            $this->stats['total_time'] /
            (float) (($this->stats['num_queries'] > 0) ? $this->stats['num_queries'] : 1);

        return $this->stats;
    }

    public function checkTable()
    {
        if (! $this->table) {
            throw new \Exception('Table is not defined.');
        }

        return true;
    }

    public function reset()
    {
        $this->where = '';
        $this->joins = '';
        $this->order = '';
        $this->groups = '';
        $this->having = '';
        $this->distinct = '';
        $this->limit = '';
        $this->offset = '';
        $this->sql = '';
    }

    //!--------------------------------------------------------------------------
    //! SQL builder methods
    //!--------------------------------------------------------------------------

    protected function parseCondition($field, $value = null, $join = '', $escape = true)
    {
        if (is_string($field)) {
            if (null === $value) {
                return $join.' '.trim($field);
            }

            $operator = '';
            if (false !== strpos($field, ' ')) {
                list($field, $operator) = explode(' ', $field);
            }

            if (filled($operator)) {
                switch ($operator) {
                    case '%':  $condition = ' LIKE '; break;
                    case '!%': $condition = ' NOT LIKE '; break;
                    case '@':  $condition = ' IN '; break;
                    case '!@': $condition = ' NOT IN '; break;
                    default:   $condition = $operator;
                }
            } else {
                $condition = '=';
            }

            if (empty($join)) {
                $join = ('|' == $field[0]) ? ' OR' : ' AND';
            }

            if (is_array($value)) {
                if (false === strpos($operator, '@')) {
                    $condition = ' IN ';
                }

                $value = '('.implode(',', array_map([$this, 'quote'], $value)).')';
            } else {
                $value = ($escape && ! is_numeric($value)) ? $this->quote($value) : $value;
            }

            return $join.' '.str_replace('|', '', $field).$condition.$value;
        } elseif (is_array($field)) {
            $str = '';
            foreach ($field as $key => $value) {
                $str .= $this->parseCondition($key, $value, $join, $escape);
                $join = '';
            }

            return $str;
        } else {
            throw new \Exception('Invalid where condition.');
        }
    }

    public function from($table, $reset = true)
    {
        $this->table = $table;

        if ($reset) {
            $this->reset();
        }

        return $this;
    }

    public function join($table, array $fields, $type = 'INNER')
    {
        static $joins = ['INNER', 'LEFT OUTER', 'RIGHT OUTER', 'FULL OUTER'];

        if (! in_array(strtoupper($type), $joins)) {
            throw new \Exception('Invalid join type: '.$type);
        }

        $this->joins .= ' '.$type.' JOIN '.$table.
            $this->parseCondition($fields, null, ' ON', false);

        return $this;
    }

    public function leftJoin($table, array $fields)
    {
        return $this->join($table, $fields, 'LEFT OUTER');
    }

    public function rightJoin($table, array $fields)
    {
        return $this->join($table, $fields, 'RIGHT OUTER');
    }

    public function fullJoin($table, array $fields)
    {
        return $this->join($table, $fields, 'FULL OUTER');
    }

    public function where($field, $value = null)
    {
        $join = (empty($this->where)) ? 'WHERE' : '';
        $this->where .= $this->parseCondition($field, $value, $join);

        return $this;
    }

    public function sortAsc($field)
    {
        return $this->orderBy($field, 'ASC');
    }

    public function sortDesc($field)
    {
        return $this->orderBy($field, 'DESC');
    }

    public function orderBy($field, $direction = 'ASC')
    {
        $join = (empty($this->order)) ? 'ORDER BY' : ',';
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                $field[$key] = $value.' '.$direction;
            }
        } else {
            $field .= ' '.$direction;
        }

        $fields = (is_array($field)) ? implode(', ', $field) : $field;
        $this->order .= $join.' '.$fields;

        return $this;
    }

    public function groupBy($field)
    {
        $join = (empty($this->order)) ? 'GROUP BY' : ',';
        $fields = (is_array($field)) ? implode(',', $field) : $field;

        $this->groups .= $join.' '.$fields;

        return $this;
    }

    public function having($field, $value = null)
    {
        $join = (empty($this->having)) ? 'HAVING' : '';
        $this->having .= $this->parseCondition($field, $value, $join);

        return $this;
    }

    public function limit($limit, $offset = null)
    {
        if (null !== $limit) {
            $this->limit = 'LIMIT '.$limit;
        }

        if (null !== $offset) {
            $this->offset($offset);
        }

        return $this;
    }

    public function offset($offset, $limit = null)
    {
        if (null !== $offset) {
            $this->offset = 'OFFSET '.$offset;
        }

        if (null !== $limit) {
            $this->limit($limit);
        }

        return $this;
    }

    public function distinct($value = true)
    {
        $this->distinct = ($value) ? 'DISTINCT' : '';

        return $this;
    }

    public function between($field, $value1, $value2)
    {
        $this->where(sprintf(
            '%s BETWEEN %s AND %s',
            $field,
            $this->quote($value1),
            $this->quote($value2)
        ));
    }

    public function select($fields = '*', $limit = null, $offset = null)
    {
        $this->checkTable();

        $fields = (is_array($fields)) ? implode(',', $fields) : $fields;
        $this->limit($limit, $offset);

        $query = [];
        $this->sql([
            'SELECT',
            $this->distinct,
            $fields,
            'FROM',
            $this->table,
            $this->joins,
            $this->where,
            $this->groups,
            $this->having,
            $this->order,
            $this->limit,
            $this->offset,
        ]);

        return $this;
    }

    public function insert(array $data)
    {
        $this->checkTable();

        if (empty($data)) {
            return $this;
        }

        $keys = implode(',', array_keys($data));
        $values = implode(',', array_values(array_map([$this, 'quote'], $data)));

        $this->sql([
            'INSERT INTO',
            $this->table,
            '('.$keys.')',
            'VALUES',
            '('.$values.')',
        ]);

        return $this;
    }

    public function update($data)
    {
        $this->checkTable();

        if (empty($data)) {
            return $this;
        }

        $values = [];
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $values[] = (is_numeric($key)) ? $value : $key.'='.$this->quote($value);
            }
        } else {
            $values[] = (string) $data;
        }

        $this->sql([
            'UPDATE',
            $this->table,
            'SET',
            implode(',', $values),
            $this->where,
        ]);

        return $this;
    }

    public function delete($where = null)
    {
        $this->checkTable();

        if (null !== $where) {
            $this->where($where);
        }

        $this->sql([
            'DELETE FROM',
            $this->table,
            $this->where,
        ]);

        return $this;
    }

    public function sql($sql = null)
    {
        if (null !== $sql) {
            $this->sql = trim((is_array($sql))
                ? array_reduce($sql, [$this, 'build']) : $sql);

            return $this;
        }

        return $this->sql;
    }

    private function connect($db)
    {
        $this->db = null;

        switch ($db['type']) {
            case 'mysql':
            case 'mysqli':
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s',
                    $db['hostname'],
                    isset($db['port']) ? $db['port'] : 3306,
                    $db['database']
                );
                $this->db = new \PDO($dsn, $db['username'], $db['password']);
                break;

            case 'pgsql':
                $dsn = sprintf(
                    'pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s',
                    $db['hostname'],
                    isset($db['port']) ? $db['port'] : 5432,
                    $db['database'],
                    $db['username'],
                    $db['password']
                );
                $this->db = new \PDO($dsn);
                break;

            case 'sqlite':
            case 'sqlite3':
                $this->db = new \PDO('sqlite:/'.$db['database']);
                break;
        }

        if (null == $this->db) {
            throw new \Exception('Undefined database.');
        }
    }

    public function getConnection()
    {
        return $this->db;
    }

    public function execute($key = null, $expire = 0)
    {
        if (! $this->db) {
            throw new \Exception('Database is not defined.');
        }

        if (null !== $key) {
            $result = $this->fetch($key);
            if ($this->is_cached) {
                return $result;
            }
        }

        $result = null;
        $this->is_cached = false;
        $this->num_rows = 0;
        $this->affected_rows = 0;
        $this->insert_id = -1;
        $this->last_query = $this->sql;

        if ($this->stats_enabled) {
            if (empty($this->stats)) {
                $this->stats = ['queries' => []];
            }

            $this->query_time = microtime(true);
        }

        if (filled($this->sql)) {
            $error = null;

            try {
                $result = $this->db->prepare($this->sql);
                if (! $result) {
                    $error = $this->db->errorInfo();
                } else {
                    $result->execute();
                    $this->num_rows = $result->rowCount();
                    $this->affected_rows = $result->rowCount();
                    $this->insert_id = $this->db->lastInsertId();
                }
            } catch (\PDOException $ex) {
                $error = $ex->getMessage();
            }

            if (null !== $error) {
                if ($this->show_sql) {
                    $error .= "\nSQL: ".$this->sql;
                }
                throw new \Exception('Database error: '.$error);
            }
        }

        if ($this->stats_enabled) {
            $time = microtime(true) - $this->query_time;
            $this->stats['queries'][] = [
                'query'   => $this->sql,
                'time'    => $time,
                'rows'    => (int) $this->num_rows,
                'changes' => (int) $this->affected_rows,
            ];
        }

        return $result;
    }

    public function many($key = null, $expire = 0)
    {
        if (empty($this->sql)) {
            $this->select();
        }

        $data = [];
        $result = $this->execute($key, $expire);

        if ($this->is_cached) {
            $data = $result;

            if ($this->stats_enabled) {
                $this->stats['cached'][$this->key_prefix.$key] = $this->sql;
            }
        } else {
            $data = $result->fetchAll(\PDO::FETCH_ASSOC);
            $this->num_rows = sizeof($data);
        }

        if (! $this->is_cached && null !== $key) {
            $this->store($key, $data, $expire);
        }

        return $data;
    }

    public function one($key = null, $expire = 0)
    {
        if (empty($this->sql)) {
            $this->limit(1)->select();
        }

        $data = $this->many($key, $expire);
        $row = (filled($data)) ? $data[0] : [];

        return $row;
    }

    public function value($name, $key = null, $expire = 0)
    {
        $row = $this->one($key, $expire);
        $value = (filled($row)) ? $row[$name] : null;

        return $value;
    }

    public function min($field, $key = null, $expire = 0)
    {
        $this->select('MIN('.$field.') min_value');

        return $this->value('min_value', $key, $expire);
    }

    public function max($field, $key = null, $expire = 0)
    {
        $this->select('MAX('.$field.') max_value');

        return $this->value('max_value', $key, $expire);
    }

    public function sum($field, $key = null, $expire = 0)
    {
        $this->select('SUM('.$field.') sum_value');

        return $this->value('sum_value', $key, $expire);
    }

    public function avg($field, $key = null, $expire = 0)
    {
        $this->select('AVG('.$field.') avg_value');

        return $this->value('avg_value', $key, $expire);
    }

    public function count($field = '*', $key = null, $expire = 0)
    {
        $this->select('COUNT('.$field.') num_rows');

        return $this->value('num_rows', $key, $expire);
    }

    public function quote($value)
    {
        if (null === $value) {
            return 'NULL';
        }

        if (is_string($value)) {
            if (null !== $this->db) {
                return $this->db->quote($value);
            }

            $value = str_replace(
                ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
                ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
                $value
            );

            return "'$value'";
        }

        return $value;
    }

    /*** Cache Methods ***/

    public function setCache($cache)
    {
        $this->cache = null;

        // Connection string
        if (is_string($cache)) {
            if ('.' == $cache[0] || '/' == $cache[0]) {
                $this->cache = $cache;
                $this->cache_type = 'file';
            } else {
                $this->setCache($this->parseConnection($cache));
            }
        }
        // Connection information
        elseif (is_array($cache)) {
            switch ($cache['type']) {
                case 'memcache':
                    $this->cache = new \Memcache();
                    $this->cache->connect($cache['hostname'], $cache['port']);
                    break;

                case 'memcached':
                    $this->cache = new \Memcached();
                    $this->cache->addServer($cache['hostname'], $cache['port']);
                    break;

                default:
                    $this->cache = $cache['type'];
            }

            $this->cache_type = $cache['type'];
        }
        // Cache object
        elseif (is_object($cache)) {
            $type = strtolower(get_class($cache));

            if (! in_array($type, ['memcached', 'memcache', 'xcache'])) {
                throw new \Exception('Invalid cache type: '.$type);
            }

            $this->cache = $cache;
            $this->cache_type = $type;
        }
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function store($key, $value, $expire = 0)
    {
        $key = $this->key_prefix.$key;

        switch ($this->cache_type) {
            case 'memcached':
                $this->cache->set($key, $value, $expire);
                break;

            case 'memcache':
                $this->cache->set($key, $value, 0, $expire);
                break;

            case 'apc':
                apc_store($key, $value, $expire);
                break;

            case 'xcache':
                xcache_set($key, $value, $expire);
                break;

            case 'file':
                $file = $this->cache.DS.md5($key);
                $data = [
                    'value'  => $value,
                    'expire' => ($expire > 0) ? (time() + $expire) : 0,
                ];

                file_put_contents($file, serialize($data));
                break;

            default:
                $this->cache[$key] = $value;
        }
    }

    public function fetch($key)
    {
        $key = $this->key_prefix.$key;

        switch ($this->cache_type) {
            case 'memcached':
                $value = $this->cache->get($key);
                $this->is_cached = (\Memcached::RES_SUCCESS == $this->cache->getResultCode());

                return $value;

            case 'memcache':
                $value = $this->cache->get($key);
                $this->is_cached = (false !== $value);

                return $value;

            case 'apc':
                return apc_fetch($key, $this->is_cached);

            case 'xcache':
                $this->is_cached = xcache_isset($key);

                return xcache_get($key);

            case 'file':
                $file = $this->cache.DS.md5($key);

                if ($this->is_cached = is_file($file)) {
                    $data = unserialize(file_get_contents($file));

                    if (0 == $data['expire'] || time() < $data['expire']) {
                        return $data['value'];
                    } else {
                        $this->is_cached = false;
                    }
                }
                break;

            default:
                return $this->cache[$key];
        }

        return null;
    }

    public function clear($key)
    {
        $key = $this->key_prefix.$key;

        switch ($this->cache_type) {
            case 'memcached':
                return $this->cache->delete($key);

            case 'memcache':
                return $this->cache->delete($key);

            case 'apc':
                return apc_delete($key);

            case 'xcache':
                return xcache_unset($key);

            case 'file':
                $file = $this->cache.DS.md5($key);

                if (is_file($file)) {
                    return unlink($file);
                }

                return false;

            default:
                if (isset($this->cache[$key])) {
                    unset($this->cache[$key]);

                    return true;
                }

                return false;
        }
    }

    public function flush()
    {
        switch ($this->cache_type) {
            case 'memcached':
                $this->cache->flush();
                break;

            case 'memcache':
                $this->cache->flush();
                break;

            case 'apc':
                apc_clear_cache();
                break;

            case 'xcache':
                xcache_clear_cache();
                break;

            case 'file':
                if ($handle = opendir($this->cache)) {
                    while (false !== ($file = readdir($handle))) {
                        if ('.' != $file && '..' != $file) {
                            unlink($this->cache.'/'.$file);
                        }
                    }

                    closedir($handle);
                }
                break;

            default:
                $this->cache = [];
                break;
        }
    }

    private function __clone()
    {
    }

    public function __destruct()
    {
        $this->db = null;
    }
}
