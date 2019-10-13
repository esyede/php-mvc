<?php

namespace Debugger;

defined('BASE') or exit('No direct script access allowed');

class Logger implements LoggerInterface
{
    public $directory;
    public $email;
    public $from_email;
    public $email_snooze = '2 days';
    public $mailer;
    private $blue_screen;

    public function __construct($directory, $email = null, BlueScreen $blue = null)
    {
        $this->directory = $directory;
        $this->email = $email;
        $this->blue_screen = $blue;
        $this->mailer = [$this, 'defaultMailer'];
    }

    public function log($message, $priority = self::INFO)
    {
        if (! $this->directory) {
            throw new \LogicException('Directory is not specified.');
        } elseif (! is_dir($this->directory)) {
            throw new \RuntimeException(
                "Directory '$this->directory' is not found or is not directory."
            );
        }

        $exceptionFile = $message instanceof \Exception
            || $message instanceof \Throwable
                ? $this->getExceptionFile($message)
                : null;

        $line = $this->formatLogLine($message, $exceptionFile);
        $file = $this->directory.DS.strtolower($priority ?: self::INFO).'.log';

        if (! @file_put_contents($file, $line.PHP_EOL, FILE_APPEND | LOCK_EX)) {
            throw new \RuntimeException(
                "Unable to write to log file '$file'. Is directory writable?"
            );
        }

        if ($exceptionFile) {
            $this->logException($message, $exceptionFile);
        }

        if (in_array($priority, [self::ERROR, self::EXCEPTION, self::CRITICAL], true)) {
            $this->sendEmail($message);
        }

        return $exceptionFile;
    }

    protected function formatMessage($message)
    {
        if ($message instanceof \Exception || $message instanceof \Throwable) {
            while ($message) {
                $tmp[] = (
                    $message instanceof \ErrorException
                    ? Helpers::errorTypeToString($message->getSeverity()).
                        ': '.$message->getMessage()
                    : Helpers::getClass($message).': '.$message->getMessage()
                ).' in '.$message->getFile().':'.$message->getLine();

                $message = $message->getPrevious();
            }

            $message = implode($tmp, "\ncaused by ");
        } elseif (! is_string($message)) {
            $message = Dumper::toText($message);
        }

        return trim($message);
    }

    protected function formatLogLine($message, $exceptionFile = null)
    {
        return implode(' ', [
            @date('[Y-m-d H-i-s]'),
            preg_replace('#\s*\r?\n\s*#', ' ', $this->formatMessage($message)),
            ' @  '.Helpers::getSource(),
            $exceptionFile ? ' @@  '.basename($exceptionFile) : null,
        ]);
    }

    public function getExceptionFile($exception)
    {
        $dir = strtr($this->directory.'/', '\\/', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
        $hash = substr(md5(preg_replace('~(Resource id #)\d+~', '$1', $exception)), 0, 10);

        foreach (new \DirectoryIterator($this->directory) as $file) {
            if (mb_strpos($file, $hash)) {
                return $dir.$file;
            }
        }

        return $dir.'exception--'.@date('Y-m-d--H-i')."--$hash.html";
    }

    protected function logException($exception, $file = null)
    {
        $file = $file ?: $this->getExceptionFile($exception);
        if ($handle = @fopen($file, 'x')) {
            ob_start();
            ob_start(function ($buffer) use ($handle) {
                fwrite($handle, $buffer);
            }, 4096);

            $bs = $this->blue_screen ?: new BlueScreen();
            $bs->render($exception);

            ob_end_flush();
            ob_end_clean();

            fclose($handle);
        }

        return $file;
    }

    protected function sendEmail($message)
    {
        $snooze = is_numeric($this->email_snooze)
            ? $this->email_snooze
            : @strtotime($this->email_snooze) - time();

        if ($this->email
        && $this->mailer
        && @filemtime($this->directory.'/email-sent') + $snooze < time()
        && @file_put_contents($this->directory.'/email-sent', 'sent')) {
            call_user_func($this->mailer, $message, implode(', ', (array) $this->email));
        }
    }

    public function defaultMailer($message, $email)
    {
        $host = preg_replace(
            '#[^\w.-]+#',
            '',
            isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : php_uname('n')
        );

        $parts = str_replace(
            ["\r\n", "\n"],
            ["\n", PHP_EOL],
            [
                'headers' => implode("\n", [
                    'From: '.($this->from_email ?: "noreply@$host"),
                    'X-Mailer: Error Debugger',
                    'Content-Type: text/plain; charset=UTF-8',
                    'Content-Transfer-Encoding: 8bit',
                ])."\n",
                'subject' => "PHP: An error occurred on the server $host",
                'body'    => $this->formatMessage($message)."\n\nsource: ".Helpers::getSource(),
            ]
        );

        mail($email, $parts['subject'], $parts['body'], $parts['headers']);
    }
}
