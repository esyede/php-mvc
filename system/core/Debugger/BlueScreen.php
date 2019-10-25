<?php

namespace Debugger;

defined('BASE') or exit('No direct script access allowed');

class BlueScreen
{
    public $info = [];
    private $panels = [];
    public $colapse_path = [];

    public function __construct()
    {
        preg_match(
            '#(.+/vendor)/debugger/debugger/src/Debugger$#',
            strtr(__DIR__, '\\', '/'),
            $matches
        );

        $this->colapse_path[] = $matches ? $matches[1] : __DIR__;
    }

    public function addPanel($panel)
    {
        if (! in_array($panel, $this->panels, true)) {
            $this->panels[] = $panel;
        }

        return $this;
    }

    public function render($exception)
    {
        $panels = $this->panels;
        $info = array_filter($this->info);
        $source = Helpers::getSource();
        $sourceIsUrl = preg_match('#^https?://#', $source);

        $title = $exception instanceof \ErrorException
            ? Helpers::errorTypeToString($exception->getSeverity())
            : Helpers::getClass($exception);

        $skipError = $sourceIsUrl
            && $exception instanceof \ErrorException
            && ! empty($exception->skippable)
                ? $source.(strpos($source, '?') ? '&' : '?').'_debugger_skip_error'
                : null;

        require __DIR__.DS.'assets'.DS.'BlueScreen'.DS.'bluescreen.php';
    }

    public static function highlightFile($file, $line, $lines = 15, array $vars = null)
    {
        $source = @file_get_contents($file);
        if ($source) {
            $source = static::highlightPhp($source, $line, $lines, $vars);

            if ($editor = Helpers::editorUri($file, $line)) {
                $href = htmlspecialchars($editor, ENT_QUOTES, 'UTF-8');
                $source = substr_replace($source, ' data-debugger-href="'.$href.'"', 4, 0);
            }

            return $source;
        }
    }

    public static function highlightPhp($source, $line, $lines = 15, array $vars = null)
    {
        if (function_exists('ini_set')) {
            ini_set('highlight.comment', '#6a737d; font-style: italic');
            ini_set('highlight.default', '#1e88e5');
            ini_set('highlight.html', '#06B');
            ini_set('highlight.keyword', '#41484f; font-weight: bold');
            ini_set('highlight.string', '#080');
        }

        $source = str_replace(["\r\n", "\r"], "\n", $source);
        $source = explode("\n", highlight_string($source, true));
        $out = $source[0];
        $source = str_replace('<br />', "\n", $source[1]);
        $out .= static::highlightLine($source, $line, $lines);

        if ($vars) {
            $out = preg_replace_callback(
                '#">\$(\w+)(&nbsp;)?</span>#',
                function ($m) use ($vars) {
                    return array_key_exists($m[1], $vars)
                            ? '" title="'
                           .str_replace(
                               '"',
                               '&quot;',
                               trim(strip_tags(Dumper::toHtml($vars[$m[1]], [Dumper::DEPTH => 1])))
                           ).$m[0] : $m[0];
                },
                $out
            );
        }

        $out = str_replace('&nbsp;', ' ', $out);

        return "<pre class='php'><div>$out</div></pre>";
    }

    public static function highlightLine($html, $line, $lines = 15)
    {
        $source = explode("\n", "\n".str_replace("\r\n", "\n", $html));
        $out = '';
        $spans = 1;
        $start = $i = max(1, min($line, count($source) - 1) - floor($lines * 2 / 3));

        // find last highlighted block
        while (--$i >= 1) {
            if (preg_match('#.*(</?span[^>]*>)#', $source[$i], $m)) {
                if ('</span>' !== $m[1]) {
                    ++$spans;
                    $out .= $m[1];
                }
                break;
            }
        }

        $source = array_slice($source, $start, $lines, true);
        end($source);
        $numWidth = strlen((string) key($source));

        foreach ($source as $n => $s) {
            $spans += substr_count($s, '<span') - substr_count($s, '</span');
            $s = str_replace(["\r", "\n"], ['', ''], $s);
            preg_match_all('#<[^>]+>#', $s, $tags);

            if ($n == $line) {
                $out .= sprintf(
                    "<span class='highlight'>%{$numWidth}s:    %s\n</span>%s",
                    $n,
                    strip_tags($s),
                    implode('', $tags[0])
                );
            } else {
                $out .= sprintf("<span class='line'>%{$numWidth}s:</span>    %s\n", $n, $s);
            }
        }

        $out .= str_repeat('</span>', $spans).'</code>';

        return $out;
    }

    public function isCollapsed($file)
    {
        $file = strtr($file, '\\', '/').'/';
        foreach ($this->colapse_path as $path) {
            $path = strtr($path, '\\', '/').'/';
            if (0 === strncmp($file, $path, strlen($path))) {
                return true;
            }
        }

        return false;
    }
}
