<?php

defined('BASE') or exit('No direct script access allowed');

class Filesystem
{
    protected $error = null;

    public function isWritable($path)
    {
        $path = str_replace(['/', '\\'], DS, $path);
        if (is_dir($path)) {
            if (DS !== $path[strlen($path) - 1]) {
                $path = $path.DS;
            }

            $file = $path.uniqid(mt_rand()).'.tmp';
            $handle = @fopen($file, 'a');
            if (false === $handle) {
                return false;
            }
            
            fclose($handle);
            unlink($file);

            return true;
        } else {
            if (! file_exists($path)) {
                return false;
            }

            $handle = @fopen($path, 'w');
            if (false === $handle) {
                return false;
            }
            
            fclose($handle);

            return true;
        }
    }

    public function isReadable($path)
    {
        $path = str_replace(['/', '\\'], DS, $path);
        if (is_dir($path)) {
            $handle = @opendir($path);
            if (false === $handle) {
                return false;
            }
            
            closedir($handle);

            return true;
        } else {
            if (! file_exists($path)) {
                return false;
            }

            $handle = @fopen($path, 'r');
            if (false === $handle) {
                return false;
            }
            
            fclose($handle);

            return true;
        }
    }

    public function createFolder($path, $chmod = 0755)
    {
        $path = str_replace(['/', '\\'], DS, $path);
        $mask = umask(0);
        if (! mkdir($path, $chmod, true)) {
            return false;
        }

        touch(rtrim($path, DS).DS.'index.html');
        umask($mask);

        return true;
    }

    public function deleteFolder($path)
    {
        $path = str_replace(['/', '\\'], DS, $path);
        if (is_dir($path)) {
            if (DS !== $path[strlen($path) - 1]) {
                $path = $path.DS;
            }

            $handle = @opendir($path);
            if (false === $handle) {
                return false;
            }
            
            while (false !== ($item = readdir($handle))) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                
                $file = $path.$item;
                if (is_dir($file)) {
                    $this->deleteFolder($file);
                } else {
                    $this->deleteFile($file);
                }
            }

            closedir($handle);
            $result = rmdir($path);
            clearstatcache();

            return $result;
        }

        return false;
    }

    public function readFolder($path, $detail = false, $deep = false)
    {
        $path = str_replace(['/', '\\'], DS, $path);
        if (is_dir($path)) {
            if (DS !== $path[strlen($path) - 1]) {
                $path = $path.DS;
            }
            
            $handle = @opendir($path);
            if (false === $handle) {
                return false;
            }
            
            $files = [];
            while (false !== ($item = readdir($handle))) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                
                if (! $detail && ! $deep) {
                    $files[] = $item;
                    continue;
                }
                
                $file = $path.$item;
                if (is_dir($file)) {
                    $details = [
                        'type' => 'folder',
                        'name' => $item,
                        'path' => $path
                    ];
                    
                    if ($deep) {
                        $details['listing'] = $this->readFolder($file, $detail, $deep - 1);
                    }
                } else {
                    $details = [
                        'type' => 'folder',
                        'name' => $item,
                        'path' => $path
                    ];
                }

                if ($detail) {
                    $stat = @stat($file);
                    if (is_array($stat)) {
                        if ('file' == $details['type']) {
                            $details['size'] = $stat['size'];
                        }

                        $details['modified'] = $stat['mtime'];
                        $details['accessed'] = $stat['atime'];
                    } else {
                        if ('file' == $details['type']) {
                            $details['size'] = @filesize($path.$item);
                        }
                        
                        $details['modified'] = filemtime($path.$item);
                        $details['accessed'] = fileatime($path.$item);
                    }
                }

                $files[] = $details;
            }

            closedir($handle);

            return $files;
        }

        return false;
    }

    public function createFile($file, $contents = null)
    {
        $file = str_replace(['/', '\\'], DS, $file);
        if (touch($file)) {
            if (is_array($contents)) {
                $contents = serialize($contents);
            }

            if (! empty($contents)) {
                return file_put_contents($file, $contents);
            }

            return true;
        }

        return false;
    }

    public function deleteFile($file)
    {
        $file = str_replace(['/', '\\'], DS, $file);
        if (@unlink($file)) {
            return true;
        }

        return false;
    }

    public function listFiles($path)
    {
        $path = str_replace(['/', '\\'], DS, $path);
        if (is_dir($path)) {
            if (DS !== $path[strlen($path) - 1]) {
                $path = $path.DS;
            }

            $handle = @opendir($path);
            if (false === $handle) {
                return false;
            }
            
            $files = [];
            while (false !== ($item = readdir($handle))) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                
                $file = $path.$item;
                if (! is_dir($file)) {
                    $files[] = $item;
                }
            }

            closedir($handle);

            return $files;
        }

        return false;
    }

    public function listFolders($path, $deep = false)
    {
        $path = str_replace(['/', '\\'], DS, $path);
        if (is_dir($path)) {
            if (DS !== $path[strlen($path) - 1]) {
                $path = $path.DS;
            }

            $handle = @opendir($path);
            if (false === $handle) {
                return false;
            }
            
            $dirs = [];
            while (false !== ($item = readdir($handle))) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $file = $path.$item;
                if (is_dir($file)) {
                    if ($deep) {
                        $dirs[] = [
                            'name' => $item,
                            'sub' => $this->listFolders($file, $deep - 1)
                        ];
                    } else {
                        $dirs[] = $item;
                    }
                }
            }

            closedir($handle);

            return $dirs;
        }

        return false;
    }

    public function size($path, $format = false)
    {
        $path = str_replace(['/', '\\'], DS, $path);
        $total = 0;
        if (is_dir($path)) {
            if (DS !== $path[strlen($path) - 1]) {
                $path = $path.DS;
            }

            $handle = @opendir($path);
            if (false === $handle) {
                return false;
            }

            $dirs = [];
            while (false !== ($item = readdir($handle))) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $file = $path.$item;
                $total += $this->size($file, false);
            }
            closedir($handle);

            return $format ? $this->formatSize($total) : $total;
        } else {
            if (PHP_OS_FAMILY === 'Windows') {
                $total = exec('for %v in ("'.$path.'") do @echo %~zv');
            } else {
                $total = exec("perl -e 'printf \"%d\n\",(stat(shift))[7];' ".$path);
            }

            if ('0' == $total) {
                $total = @filesize($path);
            }

            return $format ? $this->formatSize($total) : $total;
        }
    }

    public function copy($from, $to)
    {
        $from = str_replace(['/', '\\'], DS, $from);
        $to = str_replace(['/', '\\'], DS, $to);
        if (! file_exists($from)) {
            return false;
        }

        return copy($from, $to);
    }

    public function rename($from, $to)
    {
        $from = str_replace(['/', '\\'], DS, $from);
        $to = str_replace(['/', '\\'], DS, $to);
        if (! file_exists($from)) {
            return false;
        }

        return rename($from, $to);
    }

    public function delete($path, array $files = [])
    {
        $path = str_replace(['/', '\\'], DS, $path);
        if (is_dir($path)) {
            if (DS !== $path[strlen($path) - 1]) {
                $path = $path.DS;
            }

            if (! empty($files)) {
                foreach ($files as $item) {
                    if (! $this->delete($path.$item)) {
                        return false;
                    }
                }

                return true;
            } else {
                return $this->deleteFolder($path);
            }
        } else {
            return $this->deleteFile($path);
        }
    }

    public function getError()
    {
        return $this->error;
    }

    protected function formatSize($size)
    {
        $units = [' B', ' KB', ' MB', ' GB', ' TB'];
        for ($i = 0; $size >= 1024 && $i < 4; ++$i) {
            $size /= 1024;
        }

        return round($size, 2).$units[$i];
    }
}
