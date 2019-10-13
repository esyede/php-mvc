<?php

defined('BASE') or exit('No direct script access allowed');

class Response
{
    public $status_codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    ];

    public function setHeader($key, $value)
    {
        if (headers_sent()) {
            return false;
        }
        header($key.': '.$value);

        return true;
    }

    public function getHeader($key)
    {
        $default = getallheaders();
        $custom = headers_list();

        if (false !== $this->arrayHasLike($key, $custom)) {
            $index = $this->arrayHasLike($key, $custom);
            $header = explode(':', $custom[$index]);

            return trim($header[1]);
        } elseif (array_key_exists($key, $default)) {
            return $default[$key];
        } else {
            return false;
        }
    }

    public function setStatus($code)
    {
        return http_response_code($code);
    }

    public function getStatus($code = null)
    {
        return [
            'code' => is_null($code) ? http_response_code() : $code,
            'text' => $this->status_codes[$code],
        ];
    }

    private function arrayHasLike($element, $array)
    {
        foreach ($array as $key => $value) {
            if (false !== strpos($value, $element)) {
                return $key;
            }
        }

        return false;
    }
}
