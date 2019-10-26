<?php

defined('BASE') or exit('No direct script access allowed');

class Upload
{
    private $file = [];
    private $allowed_types = [];
    private $max_width = 0;
    private $max_height = 0;
    private $max_size = 0; // Max upload size (in KB)
    private $upload_path = false;
    private $error = false;

    public function __construct(array $config = [])
    {
        $this->init($config);
    }

    public function init(array $config = [])
    {
        if (array_key_exists('allowed_types', $config)) {
            $this->allowed_types = $config['allowed_types'];
        }

        if (array_key_exists('max_width', $config)) {
            $this->max_width = $config['max_width'];
        }

        if (array_key_exists('max_height', $config)) {
            $this->max_height = $config['max_height'];
        }

        if (array_key_exists('max_size', $config)) {
            $this->max_size = $config['max_size'];
        }

        if (array_key_exists('upload_path', $config)) {
            $this->upload_path = uploads_path($config['upload_path']);
        }
    }

    public function handle($field)
    {
        $this->file = $field;

        if ($this->checkAllowedTypes()
        && $this->checkMaxSize()
        && $this->checkUploadPath()) {
            if (is_uploaded_file($this->file['tmp_name'])) {
                $destination = $this->upload_path.$this->file['name'];

                if (move_uploaded_file($this->file['tmp_name'], $destination)) {
                    $uploaded = true;
                } else {
                    $this->error = lang('upload', 'upload_error');
                    $uploaded = false;
                }
            } else {
                $this->error = lang('upload', 'upload_error');
                $uploaded = false;
            }

            return $uploaded;
        }

        return false;
    }

    private function checkAllowedTypes()
    {
        $imageExtension = ['jpg', 'png', 'gif'];
        if (count($this->allowed_types) > 0) {
            $info = pathinfo($this->file['name']);
            $extension = $info['extension'];

            if (! in_array($extension, $this->allowed_types)) {
                $this->error = lang('upload', 'file_type_error');

                return false;
            }
            if (in_array($extension, $imageExtension)) {
                if ($this->max_width > 0 || $this->max_height > 0) {
                    list($width, $height) = getimagesize($this->file['tmp_name']);

                    if ($width > $this->max_width || $height > $this->max_height) {
                        $this->error = lang(
                            'upload',
                            'max_dimension_error',
                            [
                                    '%s' => $this->max_width,
                                    '%t' => $this->max_height,
                                ]
                        );

                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function checkMaxSize()
    {
        if ($this->max_size > 0) {
            if ($this->file['size'] > ($this->max_size * 1024)) {
                $this->error = lang('upload', 'max_size_error', $this->max_size);

                return false;
            }
        }

        return true;
    }

    private function checkUploadPath()
    {
        if (false === $this->upload_path) {
            $this->error = lang('upload', 'upload_path_needed_error');

            return false;
        }
        if (! is_dir($this->upload_path)) {
            $this->error = lang('upload', 'wrong_upload_path_error', $this->upload_path);

            return false;
        }
        if (! is_writable($this->upload_path)) {
            $this->error = lang('upload', 'permission_error');

            return false;
        }
            
        

        return true;
    }

    public function errors()
    {
        return $this->error;
    }
}
