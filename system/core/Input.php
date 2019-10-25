<?php

defined('BASE') or exit('No direct script access allowed');

class Input
{
    public function __construct()
    {
    }

    public function get($data = null, $xssClean = true)
    {
        return $this->fetchFromArray($_GET, $data, $xssClean);
    }

    public function post($data = null, $xssClean = true)
    {
        return $this->fetchFromArray($_POST, $data, $xssClean);
    }

    public function put($data = null, $xssClean = true)
    {
        parse_str(file_get_contents('php://input'), $puts);

        return $this->fetchFromArray($puts, $data, $xssClean);
    }

    public function delete($data = null, $xssClean = true)
    {
        parse_str(file_get_contents('php://input'), $delete);

        return $this->fetchFromArray($puts, $data, $xssClean);
    }

    public function getRequestMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function xssClean($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => &$value) {
                $data[$key] = $this->xssClean($value);
            }

            return $data;
        }
        // Fix &entity\n;
        $data = str_replace(['&amp;', '&lt;', '&gt;'], ['&amp;amp;', '&amp;lt;', '&amp;gt;'], $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');
        // Remove any attribute starting with "on" or xmlns
        $data = preg_replace(
            '#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu',
            '$1>',
            $data
        );
        // Remove javascript: and vbscript: protocols
        $data = preg_replace(
            '#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*'.
            'j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*'.
            's[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu',
            '$1=$2nojavascript...',
            $data
        );

        $data = preg_replace(
            '#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*'.
            's[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu',
            '$1=$2novbscript...',
            $data
        );

        $data = preg_replace(
            '#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u',
            '$1=$2nomozbinding...',
            $data
        );
        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $data = preg_replace(
            '#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?'.
            'expression[\x00-\x20]*\([^>]*+>#i',
            '$1>',
            $data
        );

        $data = preg_replace(
            '#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?'.
            'behaviour[\x00-\x20]*\([^>]*+>#i',
            '$1>',
            $data
        );

        $data = preg_replace(
            '#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?'.
            's[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*'.
            'p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu',
            '$1>',
            $data
        );
        // Remove namespaced elements (we do not need them)
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

        do {
            // Remove really unwanted tags
            $old_data = $data;
            $data = preg_replace(
                '#</*(?:applet|b(?:ase|gsound|link)'.
                '|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)'.
                '|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i',
                '',
                $data
            );
        } while ($old_data !== $data);

        return $data;
    }

    public function htmlClean($data)
    {
        $data = trim(stripslashes($data));

        return strip_tags(htmlentities($data, ENT_NOQUOTES, 'UTF-8'));
    }

    private function fetchFromArray(&$array, $index = null, $xssClean = true)
    {
        isset($index) or $index = array_keys($array);
        if (is_array($index)) {
            $output = [];
            foreach ($index as $key) {
                $output[$key] = $this->fetchFromArray($array, $key, $xssClean);
            }

            return $output;
        }

        if (isset($array[$index])) {
            $value = $array[$index];
        }
        // Does the index contains array notation?
        elseif (($count = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches)) > 1) {
            $value = $array;
            for ($i = 0; $i < $count; ++$i) {
                $key = trim($matches[0][$i], '[]');
                // Empty notation will return the value as array
                if ('' === $key) {
                    break;
                }

                if (isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    return null;
                }
            }
        } else {
            return null;
        }

        return (true === $xssClean) ? $this->xssClean($value) : $value;
    }
}
