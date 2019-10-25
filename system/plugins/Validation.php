<?php

defined('BASE') or exit('No direct script access allowed');

class Validation
{
    protected $bail = false; // Stop on first failure
    protected $keys = [];
    protected $errors = [];
    protected $labels = [];
    protected $validations = [];
    protected $child_rules = [];
    protected $child_messages = [];
    protected $messages = [];

    protected static $rules = [];

    public function __construct($data = [], $keys = [])
    {
        $this->messages = get_instance()->language('validation');
        $this->reset();
        $this->init($data, $keys);
    }

    public function init($data = [], $keys = [])
    {
        $this->reset();
        $this->keys = (filled($keys))
            ? array_intersect_key($data, array_flip($keys))
            : $data;

        return $this;
    }

    public function language(array $msg)
    {
        foreach ($msg as $key => $val) {
            if (isset($this->messages[$key])) {
                $this->messages[$key] = $val;
            }
        }
    }

    protected function _required($key, $val, $args = [])
    {
        if (isset($args[0]) && (bool) $args[0]) {
            $find = $this->get_part($this->keys, explode('.', $key), true);

            return $find[1];
        }

        return (is_null($val)
            || (is_string($val)
            && '' === trim($val))) ? false : true;
    }

    protected function _required_with($key, $val, $args, $keys)
    {
        $cond = false;
        if (isset($args[0])) {
            $required = is_array($args[0]) ? $args[0] : [$args[0]];
            $all = isset($args[1]) && (bool) $args[1];

            $empty = 0;
            foreach ($required as $req) {
                if (isset($keys[$req])
                && ! is_null($keys[$req])
                && (is_string($keys[$req]) ? '' !== trim($keys[$req]) : true)) {
                    if (! $all) {
                        $cond = true;
                        break;
                    } else {
                        ++$empty;
                    }
                }
            }
            if ($all && $empty === count($required)) {
                $cond = true;
            }
        }

        return $cond && (is_null($val) || is_string($val) && '' === trim($val));
    }

    protected function _required_without($key, $val, $args, $keys)
    {
        $cond = false;
        if (isset($args[0])) {
            $required = is_array($args[0]) ? $args[0] : [$args[0]];
            $all = isset($args[1]) && (bool) $args[1];

            $filled = 0;
            foreach ($required as $req) {
                if (! isset($keys[$req])
                || (is_null($keys[$req])
                || (is_string($keys[$req])
                && '' === trim($keys[$req])))) {
                    if (! $all) {
                        $cond = true;
                        break;
                    } else {
                        ++$filled;
                    }
                }
            }
            if ($all && $filled === count($required)) {
                $cond = true;
            }
        }

        return ! ($cond && (is_null($val) || is_string($val) && '' === trim($val)));
    }

    protected function _same($key, $val, array $args)
    {
        list($val2, $multi) = $this->get_part($this->keys, explode('.', $args[0]));

        return isset($val2) && ($val == $val2);
    }

    protected function _different($key, $val, array $args)
    {
        list($val2, $multi) = $this->get_part($this->keys, explode('.', $args[0]));

        return isset($val2) && ($val != $val2);
    }

    protected function _accepted($key, $val)
    {
        $accepted = ['yes', 'on', 1, '1', true];

        return $this->_required($key, $val)
            && in_array(strtolower($val), $accepted, true);
    }

    protected function _array($key, $val)
    {
        return is_array($val);
    }

    protected function _numeric($key, $val)
    {
        return is_numeric($val);
    }

    protected function _integer($key, $val, $args)
    {
        return (isset($args[0]) && (bool) $args[0])
            // Strict mode
            ? preg_match('/^([0-9]|-[1-9]|-?[1-9][0-9]*)$/i', $val)
            : (false !== filter_var($val, FILTER_VALIDATE_INT));
    }

    protected function _length($key, $val, $args)
    {
        $length = $this->strlength($val);

        return isset($args[1])
            // Length-between
            ? ($length >= $args[0] && $length <= $args[1])
            // Length-same
            : ((false !== $length) && ($length == $args[0]));
    }

    protected function _length_between($key, $val, $args)
    {
        $length = $this->strlength($val);

        return (false !== $length) && ($length >= $args[0]) && ($length <= $args[1]);
    }

    protected function _length_min($key, $val, $args)
    {
        $length = $this->strlength($val);

        return (false !== $length) && ($length >= $args[0]);
    }

    protected function _length_max($key, $val, $args)
    {
        $length = $this->strlength($val);

        return (false !== $length) && ($length <= $args[0]);
    }

    protected function strlength($val)
    {
        return is_string($val) ? mb_strlen($val) : false;
    }

    protected function _min($key, $val, $args)
    {
        return is_numeric($val)
            ? (function_exists('bccomp')
                ? (! (1 === bccomp($args[0], $val, 14)))
                : ($args[0] <= $val)) : false;
    }

    protected function _max($key, $val, $args)
    {
        return is_numeric($val)
            ? (function_exists('bccomp')
                ? (! (1 === bccomp($val, $args[0], 14)))
                : ($args[0] >= $val)) : false;
    }

    protected function _between($key, $val, $args)
    {
        if (! is_numeric($val)
        || ! isset($args[0])
        || ! is_array($args[0])
        || (2 !== count($args[0]))) {
            return false;
        }
        list($min, $max) = $args[0];

        return $this->_min($key, $val, [$min]) && $this->_max($key, $val, [$max]);
    }

    protected function _in($key, $val, $args)
    {
        $args[0] = (array_values($args[0]) !== $args[0])
            ? array_keys($args[0]) : $args[0];

        $strict = isset($args[1]) ? $args[1] : false;

        return in_array($val, $args[0], $strict);
    }

    protected function _not_in($key, $val, $args)
    {
        return ! $this->_in($key, $val, $args);
    }

    protected function _contains($key, $val, $args)
    {
        if (! isset($args[0])
        || ! is_string($args[0])
        || ! is_string($val)) {
            return false;
        }

        $strict = isset($args[1]) ? (bool) $args[1] : true;
        if ($strict) {
            $out = (false !== mb_strpos($val, $args[0]));
        } else {
            $out = function_exists('mb_stripos')
                ? (false !== mb_stripos($val, $args[0]))
                : (false !== stripos($val, $args[0]));
        }

        return $out;
    }

    protected function _subset($key, $val, $args)
    {
        if (! isset($args[0])) {
            return false;
        }

        if (! is_array($args[0])) {
            $args[0] = [$args[0]];
        }

        if (is_scalar($val)) {
            return $this->_in($key, $val, $args);
        }

        $inter = array_intersect($val, $args[0]);

        return array_diff($val, $inter) === array_diff($inter, $val);
    }

    protected function _contains_unique($key, $val)
    {
        return is_array($val)
            ? ($val === array_unique($val, SORT_REGULAR)) : false;
    }

    protected function _ip($key, $val)
    {
        return false !== filter_var($val, FILTER_VALIDATE_IP);
    }

    protected function _ipv4($key, $val)
    {
        return false !== filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    protected function _ipv6($key, $val)
    {
        return false !== filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    protected function _email($key, $val)
    {
        return false !== filter_var($val, FILTER_VALIDATE_EMAIL);
    }

    protected function _ascii($key, $val)
    {
        return function_exists('mb_detect_encoding')
            ? mb_detect_encoding($val, 'ASCII', true)
            : (0 === preg_match('/[^\x00-\x7F]/', $val));
    }

    protected function _email_dns($key, $val)
    {
        if ($this->_email($key, $val)) {
            $domain = ltrim(stristr($val, '@'), '@').'.';
            if (function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46')) {
                $domain = idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46);
            }

            return checkdnsrr($domain, 'ANY');
        }

        return false;
    }

    protected function _url($key, $val)
    {
        $schemes = ['http://', 'https://', 'ftp://'];
        foreach ($schemes as $scheme) {
            if (false !== strpos($val, $scheme)) {
                return false !== filter_var($val, FILTER_VALIDATE_URL);
            }
        }

        return false;
    }

    protected function _url_active($key, $val)
    {
        $schemes = ['http://', 'https://', 'ftp://'];
        foreach ($schemes as $scheme) {
            if (false !== strpos($val, $scheme)) {
                $host = parse_url(strtolower($val), PHP_URL_HOST);

                return checkdnsrr($host, 'A')
                    || checkdnsrr($host, 'AAAA')
                    || checkdnsrr($host, 'CNAME');
            }
        }

        return false;
    }

    protected function _alpha($key, $val)
    {
        return preg_match('/^([a-z])+$/i', $val);
    }

    protected function _alpha_num($key, $val)
    {
        return preg_match('/^([a-z0-9])+$/i', $val);
    }

    protected function _slug($key, $val)
    {
        return (! is_array($val))
            ? preg_match('/^([-a-z0-9_-])+$/i', $val) : false;
    }

    protected function _regex($key, $val, $args)
    {
        return preg_match($args[0], $val);
    }

    protected function _date($key, $val)
    {
        return ($val instanceof \DateTime)
            ? true : (false !== strtotime($val));
    }

    protected function _date_format($key, $val, $args)
    {
        $parsed = date_parse_from_format($args[0], $val);

        return (0 === $parsed['error_count']) && (0 === $parsed['warning_count']);
    }

    protected function _date_before($key, $val, $args)
    {
        $time1 = ($val instanceof \DateTime) ? $val->getTimestamp() : strtotime($val);
        $time2 = ($args[0] instanceof \DateTime) ? $args[0]->getTimestamp() : strtotime($args[0]);

        return $time1 < $time2;
    }

    protected function _date_after($key, $val, $args)
    {
        $time1 = ($val instanceof \DateTime) ? $val->getTimestamp() : strtotime($val);
        $time2 = ($args[0] instanceof \DateTime) ? $args[0]->getTimestamp() : strtotime($args[0]);

        return $time1 > $time2;
    }

    protected function _boolean($key, $val)
    {
        return is_bool($val);
    }

    protected function _credit_card($key, $val, $args)
    {
        if (filled($args)) {
            if (is_array($args[0])) {
                $cards = $args[0];
            } elseif (is_string($args[0])) {
                $type = $args[0];
                if (isset($args[1]) && is_array($args[1])) {
                    $cards = $args[1];

                    return in_array($type, $cards);
                }
            }
        }
        // Luhn algorithm
        $luhn = function () use ($val) {
            $number = preg_replace('/[^0-9]+/', '', $val);
            $sum = 0;

            $len = strlen($number);
            if ($len < 13) {
                return false;
            }

            for ($i = 0; $i < $len; ++$i) {
                $numb = (int) substr($number, ($len - $i - 1), 1);
                $sub = (1 == $i % 2)
                    ? ((($numb * 2) > 9)
                        ? ((($numb * 2) - 10) + 1)
                        : ($numb * 2))
                    : $numb;

                $sum += $sub;
            }

            return $sum > 0 && (0 == $sum % 10);
        };

        if ($luhn()) {
            if (! isset($cards)) {
                return true;
            } else {
                $patterns = [
                    'visa'       => '~^4[0-9]{12}(?:[0-9]{3})?$~',
                    'mastercard' => '~^(5[1-5]|2[2-7])[0-9]{14}$~',
                    'amex'       => '~^3[47][0-9]{13}$~',
                    'dinersclub' => '~^3(?:0[0-5]|[68][0-9])[0-9]{11}$~',
                    'discover'   => '~^6(?:011|5[0-9]{2})[0-9]{12}$~',
                ];

                if (isset($type)) {
                    // Tidak cocok
                    if (! isset($cards) && ! in_array($type, array_keys($patterns))) {
                        return false;
                    }

                    return 1 === preg_match($patterns[$type], $val);
                } elseif (isset($cards)) {
                    // Cocok
                    foreach ($cards as $card) {
                        if (in_array($card, array_keys($patterns))
                        && (1 === preg_match($patterns[$card], $val))) {
                            return true;
                        }
                    }
                } else {
                    // Loop semua polanya
                    foreach ($patterns as $regex) {
                        if (1 === preg_match($regex, $val)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    protected function _instance_of($key, $val, $args)
    {
        if (is_object($val)) {
            if ((is_object($args[0])
            && ($val instanceof $args[0]))
            || (get_class($val) === $args[0])) {
                return true;
            }
        }

        if (is_string($val)) {
            if (is_string($args[0])
            && (get_class($val) === $args[0])) {
                return true;
            }
        }

        return false;
    }

    protected function _optional($key, $val, $args)
    {
        // Selalu me-return true karena opsional
        return true;
    }

    protected function _keys_exists($key, $val, $args)
    {
        if (! is_array($val)
        || ! isset($args[0])
        || (0 === count($args[0]))) {
            return false;
        }

        foreach ($args[0] as $key) {
            if (! array_key_exists($key, $val)) {
                return false;
            }
        }

        return true;
    }

    public function data()
    {
        return $this->keys;
    }

    public function errors($key = null)
    {
        return (! is_null($key))
            ? (isset($this->errors[$key])
                ? $this->errors[$key] : false)
            : $this->errors;
    }

    public function error($key, $msg, array $args = [])
    {
        $msg = $this->labelize($key, $msg, $args);
        $arr = [];
        foreach ($args as $arg) {
            // TODO: Perlu di cek ulang!
            $arg = is_array($arg) ? "['".implode("','", $arg)."']" : $arg;
            $arg = ($arg instanceof \DateTime)
                ? $arg->format('Y-m-d')
                : (is_object($arg) ? get_class($arg) : $arg);
            // END TODO
            if (is_string($args[0])
            && isset($this->labels[$arg])) {
                $arg = $this->labels[$arg];
            }
            $arr[] = $arg;
        }
        $this->errors[$key][] = vsprintf($msg, $arr);
    }

    public function message($msg)
    {
        $this->validations[count($this->validations) - 1]['msg'] = $msg;

        return $this;
    }

    public function reset()
    {
        $this->keys = [];
        $this->errors = [];
        $this->validations = [];
        $this->labels = [];
    }

    protected function get_part($data, $ids, $nullable = false)
    {
        if (is_array($ids) && 0 === count($ids)) {
            return [$data, false];
        }

        if (is_scalar($data)) {
            return [null, false];
        }
        $id = array_shift($ids);
        // Pencocokan wildcard (glob)
        if ('*' === $id) {
            $arr = [];
            foreach ($data as $row) {
                list($val, $multi) = $this->get_part($row, $ids, $nullable);
                if ($multi) {
                    $arr = array_merge($arr, $val);
                } else {
                    $arr[] = $val;
                }
            }

            return [$arr, true];
        } elseif (is_null($id) || ! isset($data[$id])) {
            return (true === $nullable)
                ? [null, array_key_exists($id, $data)]
                : [null, false];
        } elseif (0 === count($ids)) {
            return (true === $nullable)
                ? [null, array_key_exists($id, $data)]
                : [$data[$id], $nullable];
        }

        return $this->get_part($data[$id], $ids, $nullable);
    }

    public function validate()
    {
        $bail = false;
        foreach ($this->validations as $v) {
            foreach ($v['keys'] as $key) {
                list($vals, $multi) = $this->get_part($this->keys, explode('.', $key), false);
                if ($this->rule_exists('optional', $key) && isset($vals)
                || ($this->rule_exists('required_with', $key)
                || $this->rule_exists('required_without', $key))) {
                    // Jangan lakukan apapun..
                } elseif (! in_array($v['rule'], ['required', 'accepted'])
                && ! $this->rule_exists('required', $key)
                && (! isset($vals) || '' === $vals || ($multi && 0 == count($vals)))) {
                    continue;
                }

                $errors = $this->get_rules();
                $callback = isset($errors[$v['rule']])
                    ? $errors[$v['rule']] : [$this, '_'.$v['rule']];

                if (! $multi) {
                    $vals = [$vals];
                } elseif (! $this->rule_exists('required', $key)) {
                    $vals = array_filter($vals);
                }

                $step = true;
                foreach ($vals as $val) {
                    $step = $step && call_user_func($callback, $key, $val, $v['args'], $this->keys);
                }

                if (! $step) {
                    $this->error($key, $v['msg'], $v['args']);

                    if ($this->bail) {
                        $bail = true;
                        break;
                    }
                }
            }

            if ($bail) {
                break;
            }
        }

        return (0 === count($this->errors()));
    }

    public function bail($bail = true)
    {
        $this->bail = (bool) $bail;
    }

    protected function get_rules()
    {
        return array_merge($this->child_rules, static::$rules);
    }

    protected function get_messages()
    {
        return array_merge($this->child_messages, $this->messages);
    }

    protected function rule_exists($name, $key)
    {
        foreach ($this->validations as $v) {
            if (($v['rule'] == $name) && in_array($key, $v['keys'])) {
                return true;
            }
        }

        return false;
    }

    protected static function assert($callback)
    {
        $error = 'The second parameter must be assigned with a valid callback';
        if (! is_callable($callback)) {
            user_error($error, E_USER_ERROR);
        }
    }

    public static function extend($name, $callback, $msg = null)
    {
        $msg = ! is_null($msg) ? $msg : 'Invalid';
        static::assert($callback);
        static::$rules[$name] = $callback;
        $this->messages[$name] = $msg;
    }

    public function extend_child($name, $callback, $msg = null)
    {
        static::assert($callback);
        $this->child_rules[$name] = $callback;
        $this->child_messages[$name] = $msg;
    }

    public function unique($keys)
    {
        if (is_array($keys)) {
            $keys = implode('_', $keys);
        }

        $org = $keys.'_rule';
        $name = $org;
        $rules = $this->get_rules();

        while (isset($rules[$name])) {
            $name = $org.'_'.rand(0, 10000);
        }

        return $name;
    }

    public function validator_exists($name)
    {
        $rules = $this->get_rules();

        return method_exists($this, '_'.$name) || isset($rules[$name]);
    }

    public function rule($rule, $keys)
    {
        $args = array_slice(func_get_args(), 2);
        if (is_callable($rule)
        && (! is_string($rule)
        && $this->validator_exists($rule))) {
            $name = $this->unique($keys);
            $msg = isset($args[0]) ? $args[0] : null;
            $this->extend_child($name, $rule, $msg);
            $rule = $name;
        }

        $errors = $this->get_rules();
        if (! isset($errors[$rule])) {
            $mtd = '_'.$rule;
            if (! method_exists($this, $mtd)) {
                $error = "The '".$rule."' rule is not registered ".
                    'with '.__CLASS__.'::extend().';
                user_error($error, E_USER_ERROR);
            }
        }

        $msgs = $this->get_messages();
        $msg = isset($msgs[$rule]) ? $msgs[$rule] : 'Invalid';
        $nolabel = (false === mb_strpos($msg, '{key}'));

        if ($nolabel) {
            $msg = '{key} '.$msg;
        }

        $this->validations[] = [
            'rule' => $rule,
            'keys' => (array) $keys,
            'args' => (array) $args,
            'msg'  => $msg,
        ];

        return $this;
    }

    public function label($val)
    {
        $last = $this->validations[count($this->validations) - 1]['keys'];
        $this->labels([$last[0] => $val]);

        return $this;
    }

    public function labels($labels = [])
    {
        $this->labels = array_merge($this->labels, $labels);

        return $this;
    }

    protected function labelize($key, $msg, $args)
    {
        if (isset($this->labels[$key])) {
            $msg = str_replace('{key}', $this->labels[$key], $msg);
            if (is_array($args)) {
                $i = 1;
                foreach ($args as $k => $v) {
                    $tag = '{key'.$i.'}';
                    $label = isset($args[$k])
                        && (is_numeric($args[$k])
                        || is_string($args[$k]))
                        && isset($this->labels[$args[$k]])
                            ? $this->labels[$args[$k]] : $tag;

                    $msg = str_replace($tag, $label, $msg);
                    ++$i;
                }
            }
        } else {
            $msg = str_replace('{key}', ucwords(str_replace('_', ' ', $key)), $msg);
        }

        return $msg;
    }

    public function rules($rules)
    {
        foreach ($rules as $type => $args) {
            if (is_array($args)) {
                foreach ($args as $arg) {
                    $arg = is_array($arg) ? $arg : (array) $arg;
                    array_unshift($arg, $type);
                    call_user_func_array([$this, 'rule'], $arg);
                }
            } else {
                $this->rule($type, $args);
            }
        }
    }

    public function with($data, $keys = [])
    {
        $clone = clone $this;
        $clone->keys = (filled($keys))
            ? array_intersect_key($data, array_flip($keys)) : $data;

        $clone->errors = [];

        return $clone;
    }

    public function map($key, $rules)
    {
        $self = $this;
        array_map(function ($rule) use ($key, $self) {
            $rule = is_array($rule) ? $rule : (array) $rule;
            $name = array_shift($rule);
            $msg = null;

            if (isset($rule['msg'])) {
                $msg = $rule['msg'];
                unset($rule['msg']);
            }

            $added = call_user_func_array(
                [$self, 'rule'],
                array_merge([$name, $key], $rule)
            );

            if (filled($msg)) {
                $added->message($msg);
            }
        }, is_array($rules) ? $rules : (array) $rules);
    }

    public function maps($rules)
    {
        $self = $this;
        array_map(function ($key) use ($rules, $self) {
            $self->map($key, $rules[$key]);
        }, array_keys($rules));
    }
}
