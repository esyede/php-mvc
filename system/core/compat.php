<?php

defined('BASE') or exit('No direct script access allowed');

//---------------------------------------------------------------
// New constants (using defaults of php 7.4 on win 7 32bit)
//---------------------------------------------------------------
defined('PHP_INT_MIN') or define('PHP_INT_MIN', ~PHP_INT_MAX);

defined('PHP_FLOAT_MIN') or define('PHP_FLOAT_MIN', 2.2250738585072E-308);
defined('PHP_FLOAT_MAX') or define('PHP_FLOAT_MAX', 1.7976931348623E+308);
defined('PHP_FLOAT_DIG') or define('PHP_FLOAT_DIG', 15);
defined('PHP_FD_SETSIZE') or define('PHP_FD_SETSIZE', 256);
defined('PHP_FLOAT_EPSILON') or define('PHP_FLOAT_EPSILON', 2.2204460492503E-16);
defined('LDAP_ESCAPE_FILTER') or define('LDAP_ESCAPE_FILTER', 0x01);
defined('LDAP_ESCAPE_DN') or define('LDAP_ESCAPE_DN', 0x02);

defined('PASSWORD_BCRYPT') or define('PASSWORD_BCRYPT', 1);
defined('PASSWORD_DEFAULT') or define('PASSWORD_DEFAULT', PASSWORD_BCRYPT);

// ---------------------------------------------------------------
// New exception classes
// ---------------------------------------------------------------
if (! class_exists('Error', false)) {
    class Error extends \Exception
    {
    }
}

if (! class_exists('AssertionError', false)) {
    class AssertionError extends \Error
    {
    }
}

if (! class_exists('ParseError', false)) {
    class ParseError extends \Error
    {
    }
}

if (! class_exists('ArithmeticError', false)) {
    class ArithmeticError extends \Error
    {
    }
}

if (! class_exists('DivisionByZeroError', false)) {
    class DivisionByZeroError extends \ArithmeticError
    {
    }
}

if (! class_exists('TypeError', false)) {
    class TypeError extends \Error
    {
    }
}

if (! class_exists('ArgumentCountError', false)) {
    class ArgumentCountError extends \TypeError
    {
    }
}

// ---------------------------------------------------------------
// PHP 5.5.0+
// ---------------------------------------------------------------
if (! function_exists('boolval')) {
    function boolval($val)
    {
        return (bool) $val;
    }
}

if (! function_exists('json_last_error_msg')) {
    function json_last_error_msg()
    {
        switch (json_last_error()) {
            case JSON_ERROR_NONE: return 'No error';
            case JSON_ERROR_DEPTH: return 'Maximum stack depth exceeded';
            case JSON_ERROR_STATE_MISMATCH: return 'State mismatch (invalid or malformed JSON)';
            case JSON_ERROR_CTRL_CHAR: return 'Control character error, possibly incorrectly encoded';
            case JSON_ERROR_SYNTAX: return 'Syntax error';
            case JSON_ERROR_UTF8: return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            default: return 'Unknown error';
        }
    }
}

if (! function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null)
    {
        $output = [];
        foreach ($input as $row) {
            $key = $value = null;
            $keySet = $valueSet = false;

            if (null !== $indexKey && array_key_exists($indexKey, $row)) {
                $keySet = true;
                $key = (string) $row[$indexKey];
            }

            if (null === $columnKey) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($columnKey, $row)) {
                $valueSet = true;
                $value = $row[$columnKey];
            }

            if ($valueSet) {
                if ($keySet) {
                    $output[$key] = $value;
                } else {
                    $output[] = $value;
                }
            }
        }

        return $output;
    }
}

if (! function_exists('hash_pbkdf2')) {
    function hash_pbkdf2($algorithm, $password, $salt, $iterations, $length = 0, $rawOutput = false)
    {
        $hashLength = strlen(hash($algorithm, '', true));
        switch ($algorithm) {
            case 'sha224':
            case 'sha256':
                $blockSize = 64;
                break;
            case 'sha384':
            case 'sha512':
                $blockSize = 128;
                break;
            default:
                $blockSize = $hashLength;
                break;
        }
        if ($length < 1) {
            $length = $hashLength;
            if (! $rawOutput) {
                $length <<= 1;
            }
        }

        $blocks = ceil($length / $hashLength);
        $digest = '';
        if (strlen($password) > $blockSize) {
            $password = hash($algorithm, $password, true);
        }

        for ($i = 1; $i <= $blocks; ++$i) {
            $ib = $block = hash_hmac($algorithm, $salt.pack('N', $i), $password, true);

            for ($j = 1; $j < $iterations; ++$j) {
                $ib ^= ($block = hash_hmac($algorithm, $block, $password, true));
            }

            $digest .= $ib;
        }

        if (! $rawOutput) {
            $digest = bin2hex($digest);
        }

        return substr($digest, 0, $length);
    }
}

if (! function_exists('password_get_info')) {
    function password_get_info($hash)
    {
        return (strlen($hash) < 60 || 1 !== sscanf($hash, '$2y$%d', $hash))
            ? [
                'algo'     => 0,
                'algoName' => 'unknown',
                'options'  => [],
            ]
            : [
                'algo'     => 1,
                'algoName' => 'bcrypt',
                'options'  => ['cost' => $hash],
            ];
    }
}

if (! is_callable('password_hash')) {
    function password_hash($pwd, $algo, array $opt = [])
    {
        static $overload;

        $overload = empty($overload)
            ? (extension_loaded('mbstring') && ini_get('mbstring.overload'))
            : $overload;

        if (1 !== $algo) {
            trigger_error(
                'password_hash(): Unknown hashing algorithm: '.(int) $algo,
                E_USER_WARNING
            );

            return null;
        }

        if (! empty($opt['cost']) && ($opt['cost'] < 4 || $opt['cost'] > 31)) {
            trigger_error(
                'password_hash(): Invalid bcrypt cost parameter specified: '.
                    (int) $opt['cost'],
                E_USER_WARNING
            );

            return null;
        }

        if (! empty($opt['salt'])
        && ($saltLen = ($overload ? mb_strlen($opt['salt'], '8bit') : strlen($opt['salt']))) < 22) {
            $message = 'password_hash(): Provided salt is too short: '.$saltLen.' expecting 22';
            trigger_error($message, E_USER_WARNING);

            return null;
        } elseif ((bool) empty($opt['salt'])) {
            if (is_callable('random_bytes')) {
                try {
                    $opt['salt'] = random_bytes(16);
                } catch (\Exception $ex) {
                    error_log(
                        'password_hash(): Error while trying to use random_bytes(): '.
                        $ex->getMessage()
                    );

                    return false;
                }
            } elseif (defined('MCRYPT_DEV_URANDOM')) {
                $opt['salt'] = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
            } elseif (DS === '/'
            && (is_readable($dev = '/dev/arandom')
            || is_readable($dev = '/dev/urandom'))) {
                if (false === ($fp = fopen($dev, 'rb'))) {
                    error_log('Unable to open '.$dev.' for reading.');

                    return false;
                }

                stream_set_chunk_size($fp, 16);

                $opt['salt'] = '';
                $len = $overload ? mb_strlen($opt['salt'], '8bit') : strlen($opt['salt']);

                for ($r = 0; $r < 16; $r = $len) {
                    if (false === ($r = fread($fp, 16 - $r))) {
                        error_log('password_hash(): Error while reading from '.$dev);

                        return false;
                    }
                    $opt['salt'] .= $r;
                }
                fclose($fp);
            } elseif (is_callable('openssl_random_pseudo_bytes')) {
                $ok = null;
                $opt['salt'] = openssl_random_pseudo_bytes(16, $ok);
                if (true !== $ok) {
                    error_log('openssl_random_pseudo_bytes() set the $cryto_strong flag to FALSE');

                    return false;
                }
            } else {
                error_log('password_hash(): No CSPRNG available.');

                return false;
            }
            $opt['salt'] = str_replace('+', '.', rtrim(base64_encode($opt['salt']), '='));
        } elseif (! preg_match('#^[a-zA-Z0-9./]+$#D', $opt['salt'])) {
            $opt['salt'] = str_replace('+', '.', rtrim(base64_encode($opt['salt']), '='));
        }

        $opt['cost'] = empty($opt['cost']) ? 10 : $opt['cost'];

        return (
            60 === strlen(
                $pwd = crypt(
                    $pwd,
                    sprintf('$2y$%02d$%s', $opt['cost'], $opt['salt'])
                )
            )
        ) ? $pwd : false;
    }
}

if (! function_exists('password_needs_rehash')) {
    function password_needs_rehash($hash, $algo, array $opt = [])
    {
        $info = password_get_info($hash);
        if ($algo !== $info['algo']) {
            return true;
        } elseif (1 === $algo) {
            $opt['cost'] = empty($opt['cost']) ? 10 : (int) $opt['cost'];

            return $info['options']['cost'] !== $opt['cost'];
        }

        return false;
    }
}

if (! function_exists('password_verify')) {
    function password_verify($pwd, $hash)
    {
        if (60 !== strlen($hash) || 60 !== strlen($pwd = crypt($pwd, $hash))) {
            return false;
        }

        $comp = 0;
        for ($i = 0; $i < 60; ++$i) {
            $comp |= (ord($pwd[$i]) ^ ord($hash[$i]));
        }

        return 0 === $comp;
    }
}

// ---------------------------------------------------------------
// PHP 5.6.0+
// ---------------------------------------------------------------
if (! function_exists('ldap_escape')) {
    function ldap_escape($subject, $ignore = '', $flags = 0)
    {
        static $char_maps = [
            LDAP_ESCAPE_FILTER => ['\\', '*', '(', ')', "\x00"],
            LDAP_ESCAPE_DN     => ['\\', ',', '=', '+', '<', '>', ';', '"', '#'],
        ];

        if (! isset($char_maps[0])) {
            $char_maps[0] = [];
            for ($i = 0; $i < 256; ++$i) {
                $char_maps[0][chr($i)] = sprintf('\\%02x', $i);
            }

            for ($i = 0, $l = count($char_maps[LDAP_ESCAPE_FILTER]); $i < $l; ++$i) {
                $chr = $char_maps[LDAP_ESCAPE_FILTER][$i];
                unset($char_maps[LDAP_ESCAPE_FILTER][$i]);
                $char_maps[LDAP_ESCAPE_FILTER][$chr] = $char_maps[0][$chr];
            }

            for ($i = 0, $l = count($char_maps[LDAP_ESCAPE_DN]); $i < $l; ++$i) {
                $chr = $char_maps[LDAP_ESCAPE_DN][$i];
                unset($char_maps[LDAP_ESCAPE_DN][$i]);
                $char_maps[LDAP_ESCAPE_DN][$chr] = $char_maps[0][$chr];
            }
        }

        $flags = (int) $flags;
        $charMap = [];
        if ($flags & LDAP_ESCAPE_FILTER) {
            $charMap += $char_maps[LDAP_ESCAPE_FILTER];
        }

        if ($flags & LDAP_ESCAPE_DN) {
            $charMap += $char_maps[LDAP_ESCAPE_DN];
        }

        if (! $charMap) {
            $charMap = $char_maps[0];
        }

        $ignore = (string) $ignore;
        for ($i = 0, $l = strlen($ignore); $i < $l; ++$i) {
            unset($charMap[$ignore[$i]]);
        }

        $result = strtr($subject, $charMap);
        if ($flags & LDAP_ESCAPE_DN) {
            if (' ' === $result[0]) {
                $result = '\\20'.substr($result, 1);
            }

            if (' ' === $result[strlen($result) - 1]) {
                $result = substr($result, 0, -1).'\\20';
            }
        }

        return $result;
    }
}

if (! function_exists('hash_equals')) {
    function hash_equals($knownString, $userString)
    {
        if (function_exists('mb_strlen')) {
            $kLen = mb_strlen($knownString, '8bit');
            $uLen = mb_strlen($userString, '8bit');
        } else {
            $kLen = strlen($knownString);
            $uLen = strlen($userString);
        }

        if ($kLen !== $uLen) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < $kLen; ++$i) {
            $result |= (ord($knownString[$i]) ^ ord($userString[$i]));
        }

        return 0 === $result;
    }
}

// ---------------------------------------------------------------
// PHP 7.0+
// ---------------------------------------------------------------
if (! function_exists('random_bytes')) {
    function random_bytes($len)
    {
        if (empty($len) || ! ctype_digit((string) $len)) {
            return false;
        }

        if (defined('MCRYPT_DEV_URANDOM')
        && false !== ($out = mcrypt_create_iv($len, MCRYPT_DEV_URANDOM))) {
            return $out;
        }

        if (is_readable('/dev/urandom')
        && false !== ($fp = fopen('/dev/urandom', 'rb'))) {
            stream_set_chunk_size($fp, $len);
            $out = fread($fp, $len);
            fclose($fp);
            if (false !== $out) {
                return $out;
            }
        }

        if (is_callable('openssl_random_pseudo_bytes')) {
            return openssl_random_pseudo_bytes($len);
        }

        return false;
    }
}

if (! function_exists('random_int')) {
    function random_int($min, $max)
    {
        $min = intval($min);
        $max = intval($max);
        if ($min > $max) {
            $message = 'Parameter 1 value must be less than or equal to parameter 2 value';
            user_error($message, E_USER_ERROR);
        }

        if ($max === $min) {
            return (int) $min;
        }

        $tries = $bits = $bytes = $mask = $shift = 0;
        $range = $max - $min;

        if (! is_int($range)) {
            $bytes = PHP_INT_SIZE;
            $mask = ~0;
        } else {
            while ($range > 0) {
                if (0 === $bits % 8) {
                    ++$bytes;
                }

                ++$bits;
                $range >>= 1;
                $mask = $mask << 1 | 1;
            }

            $shift = $min;
        }

        $val = 0;
        do {
            if ($tries > 128) {
                $message = 'random_int(): RNG is broken - too many rejections';
                throw new \RuntimeException($message);
            }

            $str = random_bytes($bytes);
            $val &= 0;
            for ($i = 0; $i < $bytes; ++$i) {
                $val |= ord($str[$i]) << ($i * 8);
            }

            $val &= $mask;
            $val += $shift;
            ++$tries;
        } while (! is_int($val) || $val > $max || $val < $min);

        return (int) $val;
    }
}

if (! function_exists('error_clear_last')) {
    function error_clear_last()
    {
        static $handler;

        if (! $handler) {
            $handler = function () {
                return false;
            };
        }
        set_error_handler($handler);
        @trigger_error('');
        restore_error_handler();
    }
}

// Internal helper function for intdiv() and preg_replace_array()
function __compat_int_arg($value, $caller, $pos)
{
    if (is_int($value)) {
        return $value;
    }

    if (! is_numeric($value) || PHP_INT_MAX <= ($value += 0) || PHP_INT_MIN >= $value) {
        $type = gettype($value);
        $message = sprintf('%s() expects parameter %d to be integer, %s given', $caller, $pos, $type);
        throw new \TypeError($message);
    }

    return (int) $value;
}

if (! function_exists('preg_replace_callback_array')) {
    function preg_replace_callback_array(array $patterns, $subject, $limit = -1, &$count = 0)
    {
        $count = 0;
        $result = (string) $subject;
        if (0 === $limit = __compat_int_arg($limit, __FUNCTION__, 3)) {
            return $result;
        }

        foreach ($patterns as $pattern => $callback) {
            $result = preg_replace_callback($pattern, $callback, $result, $limit, $c);
            $count += $c;
        }

        return $result;
    }
}

if (! function_exists('intdiv')) {
    function intdiv($dividend, $divisor)
    {
        $dividend = __compat_int_arg($dividend, __FUNCTION__, 1);
        $divisor = __compat_int_arg($divisor, __FUNCTION__, 2);

        if (0 === $divisor) {
            throw new \DivisionByZeroError('Division by zero');
        }
        if (-1 === $divisor && PHP_INT_MIN === $dividend) {
            throw new \ArithmeticError('Division of PHP_INT_MIN by -1 is not an integer');
        }

        return ($dividend - ($dividend % $divisor)) / $divisor;
    }
}

// ---------------------------------------------------------------
// PHP 7.1+
// ---------------------------------------------------------------
if (! function_exists('is_iterable')) {
    function is_iterable($var)
    {
        return is_array($var) || $var instanceof \Traversable;
    }
}

// ---------------------------------------------------------------
// PHP 7.2+
// ---------------------------------------------------------------
if (! function_exists('php_os_family')) {
    function php_os_family()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            return 'Windows';
        }

        $map = [
            'Darwin'    => 'Darwin',
            'DragonFly' => 'BSD',
            'FreeBSD'   => 'BSD',
            'NetBSD'    => 'BSD',
            'OpenBSD'   => 'BSD',
            'Linux'     => 'Linux',
            'SunOS'     => 'Solaris',
        ];

        return isset($map[PHP_OS]) ? $map[PHP_OS] : 'Unknown';
    }
}

defined('PHP_OS_FAMILY') or define('PHP_OS_FAMILY', php_os_family());

if (! function_exists('stream_isatty')) {
    function stream_isatty($stream)
    {
        if (! is_resource($stream)) {
            $type = gettype($stream);
            $message = 'stream_isatty() expects parameter 1 to be resource, '.$type.' given';
            trigger_error($message, E_USER_WARNING);

            return false;
        }

        if ('\\' === DIRECTORY_SEPARATOR) {
            $stat = @fstat($stream);

            return $stat ? 0020000 === ($stat['mode'] & 0170000) : false;
        }

        return function_exists('posix_isatty') && @posix_isatty($stream);
    }
}

if (! function_exists('sapi_windows_vt100_support')) {
    function sapi_windows_vt100_support($stream, $enable = null)
    {
        if (! is_resource($stream)) {
            $message = 'sapi_windows_vt100_support() expects parameter 1 to be resource, '.
                gettype($stream).' given';
            trigger_error($message, E_USER_WARNING);

            return false;
        }

        $meta = stream_get_meta_data($stream);
        if ('STDIO' !== $meta['stream_type']) {
            $message = 'sapi_windows_vt100_support() was not able to analyze the specified stream';
            trigger_error($message, E_USER_WARNING);

            return false;
        }

        if (false === $enable || ! stream_isatty($stream)) {
            return false;
        }

        $meta = array_map('strtolower', $meta);
        $stdin = ('php://stdin' === $meta['uri'] || 'php://fd/0' === $meta['uri']);

        return ! $stdin
            && (false !== getenv('ANSICON')
            || 'ON' === getenv('ConEmuANSI')
            || 'xterm' === getenv('TERM')
            || 'Hyper' === getenv('TERM_PROGRAM'));
    }
}

if (! function_exists('spl_object_id')) {
    function spl_object_id($object)
    {
        static $has_mask;

        if (null === $has_mask) {
            $obj = [];
            $obj = (object) $obj;
            $hash_mask = -1;

            $obFuncs = [
                'ob_clean', 'ob_end_clean',
                'ob_flush', 'ob_end_flush',
                'ob_get_contents', 'ob_get_flush',
            ];

            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
                if (isset($frame['function'][0])
                && ! isset($frame['class'])
                && 'o' === $frame['function'][0]
                && in_array($frame['function'], $obFuncs)) {
                    $frame['line'] = 0;
                    break;
                }
            }

            if (! empty($frame['line'])) {
                ob_start();
                debug_zval_dump($obj);
                $hash_mask = (int) substr(ob_get_clean(), 17);
            }

            $hash_mask ^= hexdec(substr(spl_object_hash($obj), 16 - PHP_INT_SIZE, PHP_INT_SIZE));
        }
        if (null === $hash = spl_object_hash($object)) {
            return;
        }

        return $has_mask ^ hexdec(substr($hash, 16 - PHP_INT_SIZE, PHP_INT_SIZE));
    }
}

if (! function_exists('mb_chr')) {
    function mb_chr($code, $encoding = null)
    {
        if (0x80 > $code %= 0x200000) {
            $s = chr($code);
        } elseif (0x800 > $code) {
            $s = chr(0xC0 | $code >> 6).
                chr(0x80 | $code & 0x3F);
        } elseif (0x10000 > $code) {
            $s = chr(0xE0 | $code >> 12).
                chr(0x80 | $code >> 6 & 0x3F).
                chr(0x80 | $code & 0x3F);
        } else {
            $s = chr(0xF0 | $code >> 18).
                chr(0x80 | $code >> 12 & 0x3F).
                chr(0x80 | $code >> 6 & 0x3F).
                chr(0x80 | $code & 0x3F);
        }

        if (is_null($encoding)) {
            $encoding = 'UTF-8';
        }

        $encoding = strtoupper($encoding);
        if ('8BIT' === $encoding || 'BINARY' === $encoding) {
            $encoding = 'CP850';
        }
        if ('UTF8' === $encoding) {
            $encoding = 'UTF-8';
        } else {
            $encoding = 'UTF-8';
        }

        if ('UTF-8' !== $encoding) {
            $s = mb_convert_encoding($s, $encoding, 'UTF-8');
        }

        return $s;
    }
}

if (! function_exists('mb_ord')) {
    function mb_ord($s, $encoding = null)
    {
        if (is_null($encoding)) {
            $encoding = 'UTF-8';
        }

        $encoding = strtoupper($encoding);
        if ('8BIT' === $encoding || 'BINARY' === $encoding) {
            $encoding = 'CP850';
        }
        if ('UTF8' === $encoding) {
            $encoding = 'UTF-8';
        } else {
            $encoding = 'UTF-8';
        }

        if ('UTF-8' !== $encoding) {
            $s = mb_convert_encoding($s, 'UTF-8', $encoding);
        }

        if (1 === strlen($s)) {
            return ord($s);
        }

        $code = ($s = unpack('C*', substr($s, 0, 4))) ? $s[1] : 0;
        if (0xF0 <= $code) {
            return (($code - 0xF0) << 18) +
                (($s[2] - 0x80) << 12) +
                (($s[3] - 0x80) << 6) + $s[4] - 0x80;
        }
        if (0xE0 <= $code) {
            return (($code - 0xE0) << 12) + (($s[2] - 0x80) << 6) + $s[3] - 0x80;
        }
        if (0xC0 <= $code) {
            return (($code - 0xC0) << 6) + $s[2] - 0x80;
        }

        return $code;
    }
}

// ---------------------------------------------------------------
// PHP 7.3+
// ---------------------------------------------------------------
if (! function_exists('hrtime')) {
    function hrtime($asNum = false)
    {
        $ns = microtime(false);
        $s = substr($ns, 11) - self::$startAt;
        $ns = 1E9 * (float) $ns;

        if ($asNum) {
            $ns += $s * 1E9;

            return PHP_INT_SIZE === 4 ? $ns : (int) $ns;
        }

        return [$s, (int) $ns];
    }
}

// ---------------------------------------------------------------
// PHP 7.4+
// ---------------------------------------------------------------
if (! function_exists('mb_str_split')) {
    function mb_str_split($string, $split_length = 1, $encoding = null)
    {
        if (null !== $string
        && ! is_scalar($string)
        && ! (is_object($string) && method_exists($string, '__toString'))) {
            $type = gettype($string);
            $message = 'mb_str_split() expects parameter 1 to be string, '.$type.' given';
            trigger_error($message, E_USER_WARNING);

            return null;
        }

        if ($split_length < 1) {
            $message = 'The length of each segment must be greater than zero';
            trigger_error($message, E_USER_WARNING);

            return false;
        }

        if (null === $encoding) {
            $encoding = mb_internal_encoding();
        }

        $result = [];
        $length = mb_strlen($string, $encoding);
        for ($i = 0; $i < $length; $i += $split_length) {
            $result[] = mb_substr($string, $i, $split_length, $encoding);
        }

        return $result;
    }
}

if (! function_exists('get_mangled_object_vars')) {
    function get_mangled_object_vars($obj)
    {
        if (! is_object($obj)) {
            $type = gettype($obj);
            $message = 'get_mangled_object_vars() expects parameter 1 to be object, '.$type.' given';
            trigger_error($message, E_USER_WARNING);

            return null;
        }

        if ($obj instanceof \ArrayIterator || $obj instanceof \ArrayObject) {
            $reflector = new \ReflectionClass($obj instanceof \ArrayIterator
                ? 'ArrayIterator'
                : 'ArrayObject'
            );

            $flags = $reflector->getMethod('getFlags')->invoke($obj);
            $reflector = $reflector->getMethod('setFlags');

            $reflector->invoke($obj, ($flags & \ArrayObject::STD_PROP_LIST)
                ? 0
                : \ArrayObject::STD_PROP_LIST
            );

            $arr = (array) $obj;
            $reflector->invoke($obj, $flags);
        } else {
            $arr = (array) $obj;
        }

        return array_combine(array_keys($arr), array_values($arr));
    }
}

if (! function_exists('password_algos')) {
    function password_algos()
    {
        $algos = [];
        if (defined('PASSWORD_BCRYPT')) {
            $algos[] = PASSWORD_BCRYPT;
        }

        if (defined('PASSWORD_ARGON2I')) {
            $algos[] = PASSWORD_ARGON2I;
        }

        if (defined('PASSWORD_ARGON2ID')) {
            $algos[] = PASSWORD_ARGON2ID;
        }

        return $algos;
    }
}
