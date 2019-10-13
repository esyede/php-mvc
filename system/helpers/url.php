<?php

defined('BASE') or exit('No direct script access allowed');

// Get request scheme (protocol)
if (! function_exists('request_scheme')) {
    function request_scheme()
    {
        if ((isset($_SERVER['HTTPS']) && 'on' === strtolower($_SERVER['HTTPS']))
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'])
        || (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && 'on' === $_SERVER['HTTP_FRONT_END_HTTPS'])
        || (isset($_SERVER['REQUEST_SCHEME']) && 'https' === $_SERVER['REQUEST_SCHEME'])) {
            return 'https';
        }

        return 'http';
    }
}

// Get server host
if (! function_exists('http_host')) {
    function http_host()
    {
        return $_SERVER['HTTP_HOST'];
    }
}

// Get server URIs
if (! function_exists('request_uri')) {
    function request_uri()
    {
        return $_SERVER['REQUEST_URI'];
    }
}

// Get current URL
if (! function_exists('current_url')) {
    function current_url()
    {
        return request_scheme().http_host().request_uri();
    }
}

// Get site link (relative)
if (! function_exists('site')) {
    function site($url = null)
    {
        $baseDir = str_replace('/\\', DS.DS, rtrim(BASE, '/\\'));
        $baseDir = str_replace(DS, '/', $baseDir);
        $url = ltrim($url, '/');

        return request_scheme().'://'.http_host().$baseDir.'/'.$url;
    }
}

// Get current URI segments
if (! function_exists('get_segments')) {
    function get_segments($index = null)
    {
        $segments = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
        if (is_null($index)) {
            return $segments;
        } else {
            if (array_key_exists($index, $segments)) {
                return $segments[$index];
            } else {
                return false;
            }
        }
    }
}

// Get current URI segment
if (! function_exists('current_segment')) {
    function current_segment()
    {
        return get_segments(count(get_segments()) - 1);
    }
}

// Redirect to another page
if (! function_exists('redirect')) {
    function redirect($link, $delay = 0)
    {
        if ($delay > 0) {
            header('Refresh: '.$delay.';url='.$link);
        } else {
            header('Location: '.$link);
        }
    }
}

// Get file mime-type
if (! function_exists('get_mime')) {
    function get_mime($file)
    {
        if (preg_match('/\w+$/', $file, $ext)) {
            $mimes = [
                'au'         => 'audio/basic',
                'avi'        => 'video/avi',
                'bmp'        => 'image/bmp',
                'bz2'        => 'application/x-bzip2',
                'css'        => 'text/css',
                'dtd'        => 'application/xml-dtd',
                'doc'        => 'application/msword',
                'gif'        => 'image/gif',
                'gz'         => 'application/x-gzip',
                'hqx'        => 'application/mac-binhex40',
                'html?'      => 'text/html',
                'jar'        => 'application/java-archive',
                'jpe?g|jfif?'=> 'image/jpeg',
                'js'         => 'application/x-javascript',
                'midi'       => 'audio/x-midi',
                'mp3'        => 'audio/mpeg',
                'mpe?g'      => 'video/mpeg',
                'ogg'        => 'audio/vorbis',
                'pdf'        => 'application/pdf',
                'png'        => 'image/png',
                'ppt'        => 'application/vnd.ms-powerpoint',
                'ps'         => 'application/postscript',
                'qt'         => 'video/quicktime',
                'ram?'       => 'audio/x-pn-realaudio',
                'rdf'        => 'application/rdf',
                'rtf'        => 'application/rtf',
                'sgml?'      => 'text/sgml',
                'sit'        => 'application/x-stuffit',
                'svg'        => 'image/svg+xml',
                'swf'        => 'application/x-shockwave-flash',
                'tgz'        => 'application/x-tar',
                'tiff'       => 'image/tiff',
                'txt'        => 'text/plain',
                'wav'        => 'audio/wav',
                'xls'        => 'application/vnd.ms-excel',
                'xml'        => 'application/xml',
                'zip'        => 'application/x-zip-compressed',
            ];

            foreach ($mimes as $key => $value) {
                if (preg_match('/'.$key.'/', strtolower($ext[0]))) {
                    return $value;
                }
            }
        }

        return 'application/octet-stream';
    }
}

// Send file to client (force download)
if (! function_exists('send_file')) {
    function send_file($path)
    {
        $name = $path;
        $path = uploads_path($path);
        if (! is_file($path)) {
            die('File path not found or file was deleted.');
        }

        $fh = fopen($path, 'r');
        $maxRead = 1 * 1024 * 1024; // 1 MB

        $type = 'application/octet-stream';
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $type = finfo_file($finfo, $path);
            finfo_close($finfo);
        } else {
            $type = get_mime($path);
        }

        header('Content-Type: '.$type);
        header('Content-Disposition: attachment; filename="'.$name.'"');

        while (! feof($fh)) {
            echo fread($fh, $maxRead);
            ob_flush();
        }
        exit;
    }
}
