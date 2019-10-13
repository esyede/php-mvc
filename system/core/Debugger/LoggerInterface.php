<?php

namespace Debugger;

defined('BASE') or exit('No direct script access allowed');

interface LoggerInterface
{
    const
        DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';
    const EXCEPTION = 'exception';
    const CRITICAL = 'critical';

    public function log($value, $priority = self::INFO);
}
