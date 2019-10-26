<?php

defined('BASE') or exit('No direct script access allowed');

class Cache
{
    private $config;
    private $folder;
    private $file_name;
    private $extension;
    private $default_lifetime;

    public function __construct()
    {
        $this->config = config('cache');
        $this->folder = storage_path($this->config['folder']);
        $this->extension = $this->config['extension'];
        $this->default_lifetime = $this->config['default_lifetime'];
    }

    private function isPossible()
    {
        if (! is_readable($this->folder) || ! is_writable($this->folder)) {
            if (! chmod($this->folder, 0775)) {
                $message = 'Cache folder is not readable: '.$this->folder;
                throw new \RuntimeException($message);
            }
        }

        return true;
    }

    private function getLocation($name = null)
    {
        if (true === $this->isPossible()) {
            if (is_null($name)) {
                $regex = '/[^0-9a-z\.\_\-]/i';
                $name = preg_replace($regex, '', strtolower($this->getFileName()));
            }

            $location = $this->folder.DS.md5($name).$this->getExtension();
            $location = str_replace('\\\\', DS, $location);

            return $location;
        }

        return false;
    }

    private function getContent($name = null)
    {
        if (false !== $this->getLocation()) {
            if (is_file($this->getLocation($name))) {
                $file = file_get_contents($this->getLocation($name));

                return json_decode($file, true);
            }

            return false;
        }

        return false;
    }

    public function save($key, $data, $expiration = null)
    {
        if (is_null($expiration)) {
            $expiration = $this->default_lifetime;
        }
        $data = [
            'time' => time(),
            'expire' => $expiration,
            'data' => serialize($data),
        ];

        $content = $this->getContent();
        if (true === is_array($content)) {
            $content[$key] = $data;
        } else {
            $content = [$key => $data];
        }
        file_put_contents($this->getLocation(), json_encode($content));
    }

    public function read($key, $fileName = null)
    {
        $content = $this->getContent($fileName);
        if (! isset($content[$key]['data'])) {
            return null;
        }
        return unserialize($content[$key]['data']);
    }

    public function delete($key)
    {
        $content = $this->getContent();
        if (is_array($content)) {
            if (isset($content[$key])) {
                unset($content[$key]);
                $content = json_encode($content);
                file_put_contents($this->getLocation(), $content);
            } else {
                $message = 'Cache deletion failed. Cache key does not exists: '.$key;
                throw new \Exception($message);
            }
        }
    }

    private function checkExpiry($time, $expiration)
    {
        if (0 !== $expiration) {
            $difference = time() - $time;

            return $difference > $expiration;
        }

        return false;
    }

    public function deleteExpired()
    {
        $content = $this->getContent();
        $counter = 0;
        if (is_array($content)) {
            foreach ($content as $key => $value) {
                if (true === $this->checkExpiry($value['time'], $value['expire'])) {
                    unset($content[$key]);
                    ++$counter;
                }
            }

            if ($counter > 0) {
                $content = json_encode($content);
                file_put_contents($this->getLocation(), $content);
            }
        }

        return $counter;
    }

    public function clear()
    {
        if (is_file($this->getLocation())) {
            $file = fopen($this->getLocation(), 'w');
            fclose($file);
        }
    }

    public function isCached($key)
    {
        $this->deleteExpired();
        if (false != $this->getContent()) {
            $content = $this->getContent();

            return isset($content[$key]['data']);
        }

        return false;
    }

    public function setFileName($name)
    {
        $this->file_name = $name;
    }

    public function getFileName()
    {
        return $this->file_name;
    }

    public function getFolder()
    {
        return $this->folder;
    }

    public function setExtension($ext)
    {
        $this->extension = $ext;
    }

    public function getExtension()
    {
        return $this->extension;
    }
}
