<?php

namespace Debugger;

defined('BASE') or exit('No direct script access allowed');

class Debugger
{
    const DEVELOPMENT = false;
    const PRODUCTION = true;
    const DETECT = null;

    const COOKIE_SECRET = 'debugger-debug';

    public static $production_mode = self::DETECT;
    public static $show_bar = true;

    private static $enabled = false;
    private static $reserved;
    private static $ob_level;

    public static $strict_mode = false;
    public static $scream = false;
    public static $on_fatal_error = [];

    public static $max_depth = 3;
    public static $max_len = 150;
    public static $show_location = false;

    public static $log_directory;
    public static $log_severity = 0;
    public static $email;

    const DEBUG = LoggerInterface::DEBUG;
    const INFO = LoggerInterface::INFO;
    const WARNING = LoggerInterface::WARNING;
    const ERROR = LoggerInterface::ERROR;
    const EXCEPTION = LoggerInterface::EXCEPTION;
    const CRITICAL = LoggerInterface::CRITICAL;

    public static $time;
    public static $source;
    public static $editor = null;
    public static $browser;
    public static $error_template;

    private static $cpu_usage;

    private static $blue_screen;
    private static $bar;
    private static $logger;
    private static $fire_logger;

    final public function __construct()
    {
        throw new \LogicException();
    }

    public static function enable($mode = null, $logDirectory = null, $email = null)
    {
        if (null !== $mode || null === self::$production_mode) {
            self::$production_mode = is_bool($mode) ? $mode : ! self::detectDebugMode($mode);
        }

        self::$reserved = str_repeat('t', 3e5);
        self::$time = isset($_SERVER['REQUEST_TIME_FLOAT'])
            ? $_SERVER['REQUEST_TIME_FLOAT']
            : microtime(true);

        self::$ob_level = ob_get_level();
        self::$cpu_usage = ! self::$production_mode
            && function_exists('getrusage') ? getrusage() : null;

        if (null !== $email) {
            self::$email = $email;
        }

        if (null !== $logDirectory) {
            self::$log_directory = $logDirectory;
        }

        if (self::$log_directory) {
            if (! is_dir(self::$log_directory)
            || ! preg_match('#([a-z]+:)?[/\\\\]#Ai', self::$log_directory)) {
                self::$log_directory = null;
                $text = 'Logging directory not found or is not absolute path.';
                self::exceptionHandler(new \RuntimeException($text));
            }
        }

        if (function_exists('ini_set')) {
            ini_set('display_errors', ! self::$production_mode);
            ini_set('html_errors', false);
            ini_set('log_errors', false);
        } elseif (ini_get('display_errors') != ! self::$production_mode
        && ini_get('display_errors') !== (self::$production_mode ? 'stderr' : 'stdout')) {
            $text = "Unable to set 'display_errors' because function ini_set() is disabled.";
            self::exceptionHandler(new \RuntimeException($text));
        }

        error_reporting(E_ALL | E_STRICT);

        if (! self::$enabled) {
            register_shutdown_function([__CLASS__, 'shutdownHandler']);
            set_exception_handler([__CLASS__, 'exceptionHandler']);
            set_error_handler([__CLASS__, 'errorHandler']);

            array_map('class_exists', [
                'Debugger\Bar',
                'Debugger\BlueScreen',
                'Debugger\DefaultBarPanel',
                'Debugger\Dumper',
                'Debugger\FireLogger',
                'Debugger\Helpers',
                'Debugger\Logger',
            ]);
            self::$enabled = true;
        }
    }

    public static function dispatch()
    {
        if (self::$production_mode || PHP_SAPI === 'cli') {
            return;
        } elseif (headers_sent($file, $line) || ob_get_length()) {
            throw new \Exception(
                __METHOD__.'() called after some output has been sent. '.
                    (
                        $file
                        ? "Output started at $file:$line."
                        : 'Try Debugger\OutputDebugger to find where output started.'
                    )
            );
        } elseif (self::$enabled && PHP_SESSION_ACTIVE !== session_status()) {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_trans_sid', '0');
            ini_set('session.cookie_path', '/');
            ini_set('session.cookie_httponly', '1');
            session_start();
        }
    }

    public static function isEnabled()
    {
        return self::$enabled;
    }

    public static function shutdownHandler()
    {
        if (! self::$reserved) {
            return;
        }

        $error = error_get_last();
        $errList = [
            E_ERROR,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_PARSE,
            E_RECOVERABLE_ERROR,
            E_USER_ERROR,
        ];

        if (in_array($error['type'], $errList, true)) {
            self::exceptionHandler(
                Helpers::fixStack(
                    new \ErrorException(
                        $error['message'],
                        0,
                        $error['type'],
                        $error['file'],
                        $error['line']
                    )
                ),
                false
            );
        } elseif (self::$show_bar
        && ! connection_aborted()
        && ! self::$production_mode
        && self::isHtmlMode()) {
            self::$reserved = null;
            self::removeOutputBuffers(false);
            self::getBar()->render();
        }
    }

    public static function exceptionHandler($exception, $exit = true)
    {
        if (! self::$reserved) {
            return;
        }

        self::$reserved = null;

        if (! headers_sent()) {
            $protocol = isset($_SERVER['SERVER_PROTOCOL'])
                ? $_SERVER['SERVER_PROTOCOL']
                : 'HTTP/1.1';

            $code = isset($_SERVER['HTTP_USER_AGENT'])
                && false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE ')
                    ? '503 Service Unavailable'
                    : '500 Internal Server Error';

            header("$protocol $code");

            if (self::isHtmlMode()) {
                header('Content-Type: text/html; charset=UTF-8');
            }
        }

        Helpers::improveException($exception);
        self::removeOutputBuffers(true);

        if (self::$production_mode) {
            try {
                self::log($exception, self::EXCEPTION);
            } catch (\Throwable $e) {
            } catch (\Exception $e) {
            }

            if (self::isHtmlMode()) {
                $logged = empty($e);
                require self::$error_template
                    ?: __DIR__.DS.'assets'.DS.'Debugger'.DS.'errors'.DS.'500.phtml';
            } elseif (PHP_SAPI === 'cli') {
                fwrite(STDERR, 'ERROR: application encountered an error and can not continue. '.
                    (isset($e) ? "Unable to log error.\n" : "Error was logged.\n"));
            }
        } elseif (! connection_aborted() && self::isHtmlMode()) {
            self::getBlueScreen()->render($exception);

            if (self::$show_bar) {
                self::getBar()->render();
            }
        } else {
            self::fireLog($exception);
            $s = get_class($exception).
                ('' === $exception->getMessage() ? '' : ': '.$exception->getMessage()).
                ' in '.$exception->getFile().':'.$exception->getLine().
                "\nStack trace:\n".$exception->getTraceAsString();

            try {
                $file = self::log($exception, self::EXCEPTION);

                if ($file && ! headers_sent()) {
                    header("X-Debugger-Error-Log: $file");
                }

                echo "$s\n".($file ? "(stored in $file)\n" : '');

                if ($file && self::$browser) {
                    exec(self::$browser.' '.escapeshellarg($file));
                }
            } catch (\Throwable $e) {
                echo "$s\nUnable to log error: {$e->getMessage()}\n";
            } catch (\Exception $e) {
                echo "$s\nUnable to log error: {$e->getMessage()}\n";
            }
        }

        try {
            $e = null;
            foreach (self::$on_fatal_error as $handler) {
                call_user_func($handler, $exception);
            }
        } catch (\Throwable $e) {
        } catch (\Exception $e) {
        }

        if ($e) {
            try {
                self::log($e, self::EXCEPTION);
            } catch (\Throwable $e) {
            } catch (\Exception $e) {
            }
        }

        if ($exit) {
            exit($exception instanceof \Error ? 255 : 254);
        }
    }

    public static function errorHandler($severity, $message, $file, $line, $context)
    {
        if (self::$scream) {
            error_reporting(E_ALL | E_STRICT);
        }

        if (E_RECOVERABLE_ERROR === $severity || E_USER_ERROR === $severity) {
            if (Helpers::findTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), '*::__toString')) {
                $previous = isset($context['e'])
                    && ($context['e'] instanceof \Exception
                        || $context['e'] instanceof \Throwable)
                    ? $context['e'] : null;

                $e = new \ErrorException($message, 0, $severity, $file, $line, $previous);
                $e->context = $context;

                self::exceptionHandler($e);
            }

            $e = new \ErrorException($message, 0, $severity, $file, $line);
            $e->context = $context;

            throw $e;
        } elseif (($severity & error_reporting()) !== $severity) {
            return false;
        } elseif (self::$production_mode && ($severity & self::$log_severity) === $severity) {
            $e = new \ErrorException($message, 0, $severity, $file, $line);
            $e->context = $context;

            try {
                self::log($e, self::ERROR);
            } catch (\Throwable $e) {
            } catch (\Exception $foo) {
            }

            return null;
        } elseif (! self::$production_mode && ! isset($_GET['_debugger_skip_error'])
        && (
            is_bool(self::$strict_mode)
            ? self::$strict_mode
            : ((self::$strict_mode & $severity) === $severity)
        )) {
            $e = new \ErrorException($message, 0, $severity, $file, $line);
            $e->context = $context;
            $e->skippable = true;
            self::exceptionHandler($e);
        }

        $message = 'PHP '.Helpers::errorTypeToString($severity).": $message";
        $count = &self::getBar()->getPanel('Debugger:errors')->data["$file|$line|$message"];

        if ($count++) {
            return null;
        } elseif (self::$production_mode) {
            try {
                self::log("$message in $file:$line", self::ERROR);
            } catch (\Throwable $e) {
            } catch (\Exception $foo) {
            }

            return null;
        } else {
            self::fireLog(new \ErrorException($message, 0, $severity, $file, $line));

            return self::isHtmlMode() ? null : false;
        }
    }

    private static function isHtmlMode()
    {
        return empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && PHP_SAPI !== 'cli'
            && ! preg_match('#^Content-Type: (?!text/html)#im', implode("\n", headers_list()));
    }

    private static function removeOutputBuffers($errorOccurred)
    {
        while (ob_get_level() > self::$ob_level) {
            $tmp = ob_get_status(true);
            $status = end($tmp);

            if (in_array($status['name'], ['ob_gzhandler', 'zlib output compression'])) {
                break;
            }

            $fnc = $status['chunk_size'] || ! $errorOccurred ? 'ob_end_flush' : 'ob_end_clean';

            if (! @$fnc()) {
                break;
            }
        }
    }

    public static function getBlueScreen()
    {
        if (! self::$blue_screen) {
            self::$blue_screen = new BlueScreen();
            self::$blue_screen->info = [
                'PHP '.PHP_VERSION,
                isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : null,
            ];
        }

        return self::$blue_screen;
    }

    public static function getBar()
    {
        if (! self::$bar) {
            self::$bar = new Bar();
            self::$bar->addPanel($info = new DefaultBarPanel('info'), 'Debugger:info');
            $info->cpu_usage = self::$cpu_usage;
            self::$bar->addPanel(new DefaultBarPanel('errors'), 'Debugger:errors');
        }

        return self::$bar;
    }

    public static function setLogger(LoggerInterface $logger)
    {
        self::$logger = $logger;
    }

    public static function getLogger()
    {
        if (! self::$logger) {
            self::$logger = new Logger(self::$log_directory, self::$email, self::getBlueScreen());
            self::$logger->directory = &self::$log_directory;
            self::$logger->email = &self::$email;
        }

        return self::$logger;
    }

    public static function getFireLogger()
    {
        if (! self::$fire_logger) {
            self::$fire_logger = new FireLogger();
        }

        return self::$fire_logger;
    }

    public static function dump($var, $return = false)
    {
        if ($return) {
            ob_start(function () {
            });
            Dumper::dump($var, [
                Dumper::DEPTH    => self::$max_depth,
                Dumper::TRUNCATE => self::$max_len,
            ]);

            return ob_get_clean();
        } elseif (! self::$production_mode) {
            Dumper::dump($var, [
                Dumper::DEPTH    => self::$max_depth,
                Dumper::TRUNCATE => self::$max_len,
                Dumper::LOCATION => self::$show_location,
            ]);
        }

        return $var;
    }

    public static function timer($name = null)
    {
        static $time = [];

        $now = microtime(true);
        $delta = isset($time[$name]) ? $now - $time[$name] : 0;
        $time[$name] = $now;

        return $delta;
    }

    public static function barDump($var, $title = null, array $options = null)
    {
        if (! self::$production_mode) {
            static $panel;

            if (! $panel) {
                self::getBar()->addPanel($panel = new DefaultBarPanel('dumps'));
            }

            $panel->data[] = [
                'title' => $title,
                'dump'  => Dumper::toHtml($var, (array) $options + [
                    Dumper::DEPTH    => self::$max_depth,
                    Dumper::TRUNCATE => self::$max_len,
                    Dumper::LOCATION => self::$show_location
                        ?: Dumper::LOCATION_CLASS | Dumper::LOCATION_SOURCE,
                ]),
            ];
        }

        return $var;
    }

    public static function log($message, $priority = LoggerInterface::INFO)
    {
        return self::getLogger()->log($message, $priority);
    }

    public static function fireLog($message)
    {
        if (! self::$production_mode) {
            return self::getFireLogger()->log($message);
        }
    }

    public static function detectDebugMode($list = null)
    {
        $addr = isset($_SERVER['REMOTE_ADDR'])
            ? $_SERVER['REMOTE_ADDR']
            : php_uname('n');

        $secret = isset($_COOKIE[self::COOKIE_SECRET])
            && is_string($_COOKIE[self::COOKIE_SECRET])
                ? $_COOKIE[self::COOKIE_SECRET]
                : null;

        $list = is_string($list)
            ? preg_split('#[,\s]+#', $list)
            : (array) $list;

        if (! isset($_SERVER['HTTP_X_FORWARDED_FOR'])
        && ! isset($_SERVER['HTTP_FORWARDED'])) {
            $list[] = '127.0.0.1';
            $list[] = '::1';
        }

        return in_array($addr, $list, true) || in_array("$secret@$addr", $list, true);
    }
}
