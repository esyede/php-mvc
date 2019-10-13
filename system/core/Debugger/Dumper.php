<?php

namespace Debugger;

defined('BASE') or exit('No direct script access allowed');

use Debugger;

class Dumper
{
    const
        // how many nested levels of array/object properties display (defaults to 4)
        DEPTH = 'depth';
    const // how truncate long strings? (defaults to 150)
        TRUNCATE = 'truncate';
    const // collapse top array/object or how big are collapsed? (defaults to 14)
        COLLAPSE = 'collapse';
    const // how big array/object are collapsed? (defaults to 7)
        COLLAPSE_COUNT = 'collapsecount';
    const // show location string? (defaults to 0)
        LOCATION = 'location';
    const // custom exporters for objects (defaults to Dumper::$objectexporters)
        OBJECT_EXPORTERS = 'exporters';
    const // will be rendered using JavaScript
        LIVE = 'live';

    const
        // shows where dump was called
        LOCATION_SOURCE = 1;
    const // appends clickable anchor
        LOCATION_LINK = 2;
    const // shows where class is defined
        LOCATION_CLASS = 4;

    public static $terminal_colors = [
        'bool'       => '1;33',
        'null'       => '1;33',
        'number'     => '1;32',
        'string'     => '1;36',
        'array'      => '1;31',
        'key'        => '1;37',
        'object'     => '1;31',
        'visibility' => '1;30',
        'resource'   => '1;37',
        'indent'     => '1;30',
    ];

    public static $resources = [
        'stream'         => 'stream_get_meta_data',
        'stream-context' => 'stream_context_get_options',
        'curl'           => 'curl_getinfo',
    ];

    public static $object_exporters = [
        'Closure'                => '\Debugger\Dumper::exportClosure',
        'SplFileInfo'            => '\Debugger\Dumper::exportSplFileInfo',
        'SplObjectStorage'       => '\Debugger\Dumper::exportSplObjectStorage',
        '__PHP_Incomplete_Class' => '\Debugger\Dumper::exportPhpIncompleteClass',
    ];

    public static $live_prefix;
    private static $live_storage = [];

    public static function dump($var, array $options = null)
    {
        if (PHP_SAPI !== 'cli'
        && ! preg_match('#^Content-Type: (?!text/html)#im', implode("\n", headers_list()))) {
            echo self::toHtml($var, $options);
        } elseif (self::detectColors()) {
            echo self::toTerminal($var, $options);
        } else {
            echo self::toText($var, $options);
        }

        return $var;
    }

    public static function toHtml($var, array $options = null)
    {
        $options = (array) $options + [
            self::DEPTH            => 4,
            self::TRUNCATE         => 150,
            self::COLLAPSE         => 14,
            self::COLLAPSE_COUNT   => 7,
            self::OBJECT_EXPORTERS => null,
        ];

        $loc = &$options[self::LOCATION];
        $loc = true === $loc ? ~0 : (int) $loc;

        $options[self::OBJECT_EXPORTERS] = (array) $options[self::OBJECT_EXPORTERS] +
            self::$object_exporters;

        uksort(
            $options[self::OBJECT_EXPORTERS],
            function ($a, $b) {
                return '' === $b || (class_exists($a, false)
                    && ($rc = new \ReflectionClass($a))
                    && $rc->isSubclassOf($b)) ? -1 : 1;
            }
        );

        $live = ! empty($options[self::LIVE])
            && $var
            && (is_array($var)
            || is_object($var)
            || is_resource($var));

        list($file, $line, $code) = $loc ? self::findLocation() : null;

        $locAttrs = $file
            && $loc
            & self::LOCATION_SOURCE ? Helpers::formatHtml(
                ' title="%in file % on line %" data-debugger-href="%"',
                "$code\n",
                $file,
                $line,
                Helpers::editorUri($file, $line)
            ) : null;

        return '<pre class="debugger-dump'.
            ($live && true === $options[self::COLLAPSE] ? ' debugger-collapsed' : '').
            '"'.$locAttrs
         .(
             $live ? " data-debugger-dump='".
                json_encode(self::toJson($var, $options), JSON_HEX_APOS | JSON_HEX_AMP)."'>"
                : '>'
         )
         .($live ? '' : self::dumpVar($var, $options))
         .(
             $file && $loc & self::LOCATION_LINK
                ? '<small>in '.Helpers::editorLink($file, $line).'</small>'
                : ''
         )
         ."</pre>\n";
    }

    public static function toText($var, array $options = null)
    {
        return htmlspecialchars_decode(strip_tags(self::toHtml($var, $options)), ENT_QUOTES);
    }

    public static function toTerminal($var, array $options = null)
    {
        return htmlspecialchars_decode(strip_tags(preg_replace_callback(
            '#<span class="debugger-dump-(\w+)">|</span>#',
            function ($m) {
                return "\033[".(
                    isset($m[1], Dumper::$terminal_colors[$m[1]])
                        ? Dumper::$terminal_colors[$m[1]]
                        : '0'
                ).'m';
            },
            self::toHtml($var, $options)
        )), ENT_QUOTES);
    }

    private static function dumpVar(&$var, array $options, $level = 0)
    {
        if (method_exists(__CLASS__, $m = 'dump'.gettype($var))) {
            return self::$m($var, $options, $level);
        }

        return "<span>unknown type</span>\n";
    }

    private static function dumpNull()
    {
        return "<span class=\"debugger-dump-null\">null</span>\n";
    }

    private static function dumpBoolean(&$var)
    {
        return '<span class="debugger-dump-bool">'.($var ? 'true' : 'false')."</span>\n";
    }

    private static function dumpInteger(&$var)
    {
        return "<span class=\"debugger-dump-number\">$var</span>\n";
    }

    private static function dumpDouble(&$var)
    {
        $var = is_finite($var)
            ? ($tmp = json_encode($var)).(false === strpos($tmp, '.') ? '.0' : '')
            : str_replace('.0', '', var_export($var, true)); // workaround untuk PHP 7.0.2

        return "<span class=\"debugger-dump-number\">$var</span>\n";
    }

    private static function dumpString(&$var, $options)
    {
        $string = htmlspecialchars(
            self::encodeString($var, $options[self::TRUNCATE]),
            ENT_NOQUOTES,
            'UTF-8'
        );

        return '<span class="debugger-dump-string">"'.$string.'"</span>'.
            (strlen($var) > 1 ? ' ('.strlen($var).')' : '')."\n";
    }

    private static function dumpArray(&$var, $options, $level)
    {
        static $marker;

        if (null === $marker) {
            $marker = uniqid("\x00", true);
        }

        $out = '<span class="debugger-dump-array">array</span> (';

        if (empty($var)) {
            return $out.")\n";
        } elseif (isset($var[$marker])) {
            return $out.(count($var) - 1).") [ <i>RECURSION</i> ]\n";
        } elseif (! $options[self::DEPTH] || $level < $options[self::DEPTH]) {
            $collapsed = $level
                ? count($var) >= $options[self::COLLAPSE_COUNT]
                : (is_int($options[self::COLLAPSE])
                    ? count($var) >= $options[self::COLLAPSE]
                    : $options[self::COLLAPSE]);

            $out = '<span class="debugger-toggle'.
                ($collapsed ? ' debugger-collapsed' : '').'">'
             .$out.count($var).")</span>\n<div".
                ($collapsed ? ' class="debugger-collapsed"' : '').'>';

            $var[$marker] = true;

            foreach ($var as $k => &$v) {
                if ($k !== $marker) {
                    $str = htmlspecialchars(
                        self::encodeString($k, $options[self::TRUNCATE]),
                        ENT_NOQUOTES,
                        'UTF-8'
                    );

                    $k = preg_match('#^\w{1,50}\z#', $k) ? $k : '"'.$str.'"';
                    $out .= '<span class="debugger-dump-indent">   '.
                        str_repeat('|  ', $level).'</span>'.
                        '<span class="debugger-dump-key">'.$k.'</span> => '.
                        self::dumpVar($v, $options, $level + 1);
                }
            }

            unset($var[$marker]);

            return $out.'</div>';
        } else {
            return $out.count($var).") [ ... ]\n";
        }
    }

    private static function dumpObject(&$var, $options, $level)
    {
        $fields = self::exportObject($var, $options[self::OBJECT_EXPORTERS]);
        $editor = null;

        if ($options[self::LOCATION] & self::LOCATION_CLASS) {
            $rc = $var instanceof \Closure
                ? new \ReflectionFunction($var)
                : new \ReflectionClass($var);

            $editor = Helpers::editorUri($rc->getFileName(), $rc->getStartLine());
        }

        $out = '<span class="debugger-dump-object"'
          .(
              $editor
                ? Helpers::formatHtml(
                    ' title="Declared in file % on line %" data-debugger-href="%"',
                    $rc->getFileName(),
                    $rc->getStartLine(),
                    $editor
                )
                : ''
          )
          .'>'.htmlspecialchars(Helpers::getClass($var)).'</span>'.
            '<span class="debugger-dump-hash">#'.
            substr(md5(spl_object_hash($var)), 0, 4).'</span>';

        static $list = [];

        if (empty($fields)) {
            return $out."\n";
        } elseif (in_array($var, $list, true)) {
            return $out." { <i>RECURSION</i> }\n";
        } elseif (! $options[self::DEPTH]
        || $level < $options[self::DEPTH]
        || $var instanceof \Closure) {
            $collapsed = $level
                ? count($fields) >= $options[self::COLLAPSE_COUNT]
                : (
                    is_int($options[self::COLLAPSE])
                    ? count($fields) >= $options[self::COLLAPSE]
                    : $options[self::COLLAPSE]
                );

            $out = '<span class="debugger-toggle'.
                ($collapsed ? ' debugger-collapsed' : '').'">'
              .$out."</span>\n<div".
                ($collapsed ? ' class="debugger-collapsed"' : '').'>';

            $list[] = $var;

            foreach ($fields as $k => &$v) {
                $vis = '';

                if (isset($k[0]) && "\x00" === $k[0]) {
                    $vis = ' <span class="debugger-dump-visibility">'.
                        ('*' === $k[1] ? 'protected' : 'private').'</span>';
                    $k = substr($k, strrpos($k, "\x00") + 1);
                }

                $k = preg_match('#^\w{1,50}\z#', $k)
                    ? $k
                    : '"'.htmlspecialchars(
                        self::encodeString($k, $options[self::TRUNCATE]),
                        ENT_NOQUOTES,
                        'UTF-8'
                    ).'"';

                $out .= '<span class="debugger-dump-indent">   '.str_repeat('|  ', $level).
                    '</span><span class="debugger-dump-key">'.$k."</span>$vis => "
                  .self::dumpVar($v, $options, $level + 1);
            }

            array_pop($list);

            return $out.'</div>';
        } else {
            return $out." { ... }\n";
        }
    }

    private static function dumpResource(&$var, $options, $level)
    {
        $type = get_resource_type($var);
        $out = '<span class="debugger-dump-resource">'.
            htmlspecialchars($type, ENT_IGNORE, 'UTF-8').
            ' resource</span> '.
            '<span class="debugger-dump-hash">#'.intval($var).'</span>';

        if (isset(self::$resources[$type])) {
            $out = '<span class="debugger-toggle debugger-collapsed">'.
                $out."</span>\n<div class=\"debugger-collapsed\">";

            foreach (call_user_func(self::$resources[$type], $var) as $k => $v) {
                $out .= '<span class="debugger-dump-indent">   '.str_repeat('|  ', $level).
                '</span><span class="debugger-dump-key">'.
                htmlspecialchars($k, ENT_IGNORE, 'UTF-8').'</span> => '.
                self::dumpVar($v, $options, $level + 1);
            }

            return $out.'</div>';
        }

        return "$out\n";
    }

    private static function toJson(&$var, $options, $level = 0)
    {
        if (is_bool($var) || is_null($var) || is_int($var)) {
            return $var;
        } elseif (is_float($var)) {
            return is_finite($var)
                ? (strpos($tmp = json_encode($var), '.')
                    ? $var : ['number' => "$tmp.0"])
                : ['type' => (string) $var];
        } elseif (is_string($var)) {
            return self::encodeString($var, $options[self::TRUNCATE]);
        } elseif (is_array($var)) {
            static $marker;

            if (null === $marker) {
                $marker = uniqid("\x00", true);
            }

            if (isset($var[$marker]) || $level >= $options[self::DEPTH]) {
                return [null];
            }

            $res = [];
            $var[$marker] = true;

            foreach ($var as $k => &$v) {
                if ($k !== $marker) {
                    $k = preg_match('#^\w{1,50}\z#', $k)
                    ? $k
                    : '"'.self::encodeString($k, $options[self::TRUNCATE]).'"';

                    $res[] = [$k, self::toJson($v, $options, $level + 1)];
                }
            }

            unset($var[$marker]);

            return $res;
        } elseif (is_object($var)) {
            $obj = &self::$live_storage[spl_object_hash($var)];

            if ($obj && $obj['level'] <= $level) {
                return ['object' => $obj['id']];
            }

            if ($options[self::LOCATION] & self::LOCATION_CLASS) {
                $rc = $var instanceof \Closure
                    ? new \ReflectionFunction($var)
                    : new \ReflectionClass($var);

                $editor = Helpers::editorUri($rc->getFileName(), $rc->getStartLine());
            }

            static $counter = 1;

            $obj = $obj ?: [
                'id'     => self::$live_prefix.'0'.$counter++,
                'name'   => Helpers::getClass($var),
                'editor' => empty($editor)
                    ? null
                    : [
                        'file' => $rc->getFileName(),
                        'line' => $rc->getStartLine(),
                        'url'  => $editor,
                    ],
                'level'  => $level,
                'object' => $var,
            ];

            if ($level < $options[self::DEPTH] || ! $options[self::DEPTH]) {
                $obj['level'] = $level;
                $obj['items'] = [];

                foreach (self::exportObject($var, $options[self::OBJECT_EXPORTERS]) as $k => $v) {
                    $vis = 0;

                    if (isset($k[0]) && "\x00" === $k[0]) {
                        $vis = '*' === $k[1] ? 1 : 2;
                        $k = substr($k, strrpos($k, "\x00") + 1);
                    }

                    $k = preg_match('#^\w{1,50}\z#', $k)
                        ? $k
                        : '"'.self::encodeString($k, $options[self::TRUNCATE]).'"';

                    $obj['items'][] = [$k, self::toJson($v, $options, $level + 1), $vis];
                }
            }

            return ['object' => $obj['id']];
        } elseif (is_resource($var)) {
            $obj = &self::$live_storage[(string) $var];

            if (! $obj) {
                $type = get_resource_type($var);
                $obj = [
                    'id'   => self::$live_prefix.(int) $var,
                    'name' => $type.' resource',
                ];

                if (isset(self::$resources[$type])) {
                    foreach (call_user_func(self::$resources[$type], $var) as $k => $v) {
                        $obj['items'][] = [$k, self::toJson($v, $options, $level + 1)];
                    }
                }
            }

            return ['resource' => $obj['id']];
        }

        return ['type' => 'unknown type'];
    }

    public static function fetchLiveData()
    {
        $res = [];

        foreach (self::$live_storage as $obj) {
            $id = $obj['id'];
            unset($obj['level'], $obj['object'], $obj['id']);
            $res[$id] = $obj;
        }

        self::$live_storage = [];

        return $res;
    }

    public static function encodeString($s, $maxLength = null)
    {
        static $table;

        if (null === $table) {
            foreach (array_merge(range("\x00", "\x1F"), range("\x7F", "\xFF")) as $ch) {
                $table[$ch] = '\x'.str_pad(dechex(ord($ch)), 2, '0', STR_PAD_LEFT);
            }

            $table['\\'] = '\\\\';
            $table["\r"] = '\r';
            $table["\n"] = '\n';
            $table["\t"] = '\t';
        }

        if (preg_match('#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u', $s) || preg_last_error()) {
            if ($shortened = ($maxLength && strlen($s) > $maxLength)) {
                $s = substr($s, 0, $maxLength);
            }

            $s = strtr($s, $table);
        } elseif ($shortened = ($maxLength && strlen(utf8_decode($s)) > $maxLength)) {
            if (function_exists('mb_substr')) {
                $s = mb_substr($s, 0, $maxLength, 'UTF-8');
            } else {
                $i = $len = 0;

                do {
                    if (($s[$i] < "\x80" || $s[$i] >= "\xC0") && (++$len > $maxLength)) {
                        $s = substr($s, 0, $i);
                        break;
                    }
                } while (isset($s[++$i]));
            }
        }

        return $s.(empty($shortened) ? '' : ' ... ');
    }

    private static function exportObject($obj, array $exporters)
    {
        foreach ($exporters as $type => $dumper) {
            if (! $type || $obj instanceof $type) {
                return call_user_func($dumper, $obj);
            }
        }

        return (array) $obj;
    }

    private static function exportClosure(\Closure $obj)
    {
        $rc = new \ReflectionFunction($obj);
        $res = [];

        foreach ($rc->getParameters() as $param) {
            $res[] = '$'.$param->getName();
        }

        return [
            'file'       => $rc->getFileName(),
            'line'       => $rc->getStartLine(),
            'variables'  => $rc->getStaticVariables(),
            'parameters' => implode(', ', $res),
        ];
    }

    private static function exportSplFileInfo(\SplFileInfo $obj)
    {
        return ['path' => $obj->getPathname()];
    }

    private static function exportSplObjectStorage(\SplObjectStorage $obj)
    {
        $res = [];
        foreach (clone $obj as $item) {
            $res[] = [
                'object' => $item,
                'data'   => $obj[$item],
            ];
        }

        return $res;
    }

    private static function exportPhpIncompleteClass(\__PHP_Incomplete_Class $obj)
    {
        $info = [
            'className' => null,
            'private'   => [],
            'protected' => [],
            'public'    => [],
        ];

        foreach ((array) $obj as $name => $value) {
            if ('__PHP_Incomplete_Class_Name' === $name) {
                $info['className'] = $value;
            } elseif (preg_match('#^\x0\*\x0(.+)\z#', $name, $m)) {
                $info['protected'][$m[1]] = $value;
            } elseif (preg_match('#^\x0(.+)\x0(.+)\z#', $name, $m)) {
                $info['private'][$m[1].'::$'.$m[2]] = $value;
            } else {
                $info['public'][$name] = $value;
            }
        }

        return $info;
    }

    private static function findLocation()
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $item) {
            if (isset($item['class']) && __CLASS__ === $item['class']) {
                $location = $item;

                continue;
            } elseif (isset($item['function'])) {
                try {
                    $reflection = isset($item['class'])
                        ? new \ReflectionMethod($item['class'], $item['function'])
                        : new \ReflectionFunction($item['function']);
                    if ($reflection->isInternal()
                    || preg_match('#\s@debuggerSkipLocation\s#', $reflection->getDocComment())) {
                        $location = $item;

                        continue;
                    }
                } catch (\ReflectionException $e) {
                }
            }

            break;
        }

        if (isset($location['file'], $location['line']) && is_file($location['file'])) {
            $lines = file($location['file']);
            $line = $lines[$location['line'] - 1];

            return [
                $location['file'],
                $location['line'],
                trim(preg_match('#\w*dump(er::\w+)?\(.*\)#i', $line, $m) ? $m[0] : $line),
            ];
        }
    }

    private static function detectColors()
    {
        return self::$terminal_colors &&
            ('ON' === getenv('ConEmuANSI')
            || false !== getenv('ANSICON')
            || 'xterm-256color' === getenv('term')
            || (defined('STDOUT')
            && function_exists('posix_isatty')
            && posix_isatty(STDOUT)));
    }
}
