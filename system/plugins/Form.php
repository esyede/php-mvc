<?php

defined('BASE') or exit('No direct script access allowed');

class Form
{
    protected $html = null;

    public function __construct()
    {
        $this->flush();
    }

    public function init($name = '', array $attr = [])
    {
        if (is_array($name)) {
            $html = '<form ';
            if (count($name) > 0) {
                foreach ($name as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        } else {
            $html = '<form name="'.$name.'" id="'.$name.'" ';
            if (count($attr) > 0) {
                foreach ($attr as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        }
        $this->html = trim($html).">\n";

        return $this;
    }

    public function input($name = '', $type = 'text', array $attr = [])
    {
        if (is_array($name)) {
            $html = '<input ';
            if (count($name) > 0) {
                foreach ($name as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        } else {
            $html = '<input type="'.$type.'" name="'.$name.'" id="'.$name.'" ';
            if (count($attr) > 0) {
                foreach ($attr as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        }
        $this->html .= trim($html).">\n";

        return $this;
    }

    public function file($name = '', $multiple = false, array $attr = [])
    {
        if (is_array($name)) {
            $html = '<input type="file" ';
            if (count($name) > 0) {
                foreach ($name as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        } else {
            if (true == $multiple) {
                $html = '<input type="file" name="'.$name.'[]"'.
                    ' id="'.$name.'[]" multiple="multiple" ';
            } else {
                $html = '<input type="file" name="'.$name.'" id="'.$name.'" ';
            }

            if (count($attr) > 0) {
                foreach ($attr as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        }
        $this->html .= trim($html).">\n";

        return $this;
    }

    public function textarea($name = '', $text = '', array $attr = [])
    {
        if (is_array($name)) {
            $html = '<textarea ';
            if (count($name) > 0) {
                foreach ($name as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        } else {
            $html = '<textarea name="'.$name.'" id="'.$name.'" ';
            if (count($attr) > 0) {
                foreach ($attr as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        }
        $this->html .= trim($html).">$text</textarea>\n";

        return $this;
    }

    public function select($name = '', array $options = [], $selected = '', array $attr = [])
    {
        if (is_array($name)) {
            $html = '<select ';
            if (count($name) > 0) {
                foreach ($name as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        } else {
            $html = '<select name="'.$name.'" id="'.$name.'" ';
            if (count($attr) > 0) {
                foreach ($attr as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        }

        $html = trim($html);
        $html .= '>';

        $dropdown = '';
        if (count($options) > 0) {
            foreach ($options as $key => $val) {
                if ($selected && $val == $selected) {
                    $dropdown .= '<option vaue="'.$val.'" selected>'.$key.'</option>';
                } else {
                    $dropdown .= '<option vaue="'.$val.'">'.$key.'</option>';
                }
            }
        }
        $this->html .= $html."\n".$dropdown."\n".'</select>';

        return $this;
    }

    public function multiselect($name = '', array $options = [], $selected = '', array $attr = [])
    {
        if (is_array($name)) {
            $html = '<select multiple="multiple" ';
            if (count($name) > 0) {
                foreach ($name as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        } else {
            $html = '<select name="'.$name.'" id="'.$name.'" multiple="multiple" ';
            if (count($attr) > 0) {
                foreach ($attr as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        }

        $html = trim($html);
        $html .= '>';

        $dropdown = '';
        if (count($options) > 0) {
            foreach ($options as $key => $val) {
                if ($selected && $val == $selected) {
                    $dropdown .= '<option vaue="'.$val.'" selected>'.$key.'</option>';
                } else {
                    $dropdown .= '<option vaue="'.$val.'">'.$key.'</option>';
                }
            }
        }
        $this->html .= $html."\n".$dropdown."\n".'</select>';

        return $this;
    }

    public function checkbox($name = '', $checked = false, $value = '', array $attr = [])
    {
        if (is_array($name)) {
            $html = '<input type="checkbox" ';
            if (count($name) > 0) {
                foreach ($name as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        } else {
            $html = '<input type="checkbox" name="'.$name.
                '" id="'.$name.'" value="'.$value.'" ';

            if (count($attr) > 0) {
                foreach ($attr as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        }

        if (true == $checked) {
            $html .= 'checked';
        }
        $this->html .= trim($html).">\n";

        return $this;
    }

    public function radio($name = '', $checked = false, $value = '', array $attr = [])
    {
        if (is_array($name)) {
            $html = '<input type="radio" ';
            if (count($name) > 0) {
                foreach ($name as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        } else {
            $html = '<input type="radio" name="'.$name.
                '" id="'.$name.'" value="'.$value.'" ';
            if (count($attr) > 0) {
                foreach ($attr as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        }

        if (true == $checked) {
            $html .= 'checked';
        }
        $this->html .= trim($html).">\n";

        return $this;
    }

    public function submit($name = '', $value = '', array $attr = [])
    {
        if (is_array($name)) {
            $html = '<input type="submit" ';
            if (count($name) > 0) {
                foreach ($name as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        } else {
            $html = '<input type="submit" name="'.$name.
                '" id="'.$name.'" value="'.$value.'" ';
            if (count($attr) > 0) {
                foreach ($attr as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        }
        $this->html .= trim($html).">\n";

        return $this;
    }

    public function reset($name = '', $value = '', array $attr = [])
    {
        if (is_array($name)) {
            $html = '<input type="reset" ';
            if (count($name) > 0) {
                foreach ($name as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        } else {
            $html = '<input type="reset" name="'.$name.
                '" id="'.$name.'" value="'.$value.'" ';
            if (count($attr) > 0) {
                foreach ($attr as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        }
        $this->html .= trim($html).">\n";

        return $this;
    }

    public function button($name = '', $content = '', array $attr = [])
    {
        if (is_array($name)) {
            $html = '<button type="button" ';
            if (count($name) > 0) {
                foreach ($name as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        } else {
            $html = '<button type="button" name="'.$name.'" id="'.$name.'" ';
            if (count($attr) > 0) {
                foreach ($attr as $key => $val) {
                    $html .= $key.'="'.$val.'" ';
                }
            }
        }
        $this->html .= trim($html).">$content</button>\n";

        return $this;
    }

    public function label($for = '', $content = '', array $attr = [])
    {
        if (is_array($for)) {
            $label = '<label ';
            if (count($for) > 0) {
                foreach ($for as $key => $val) {
                    $label .= $key.'="'.$val."' ";
                }
            }
        } else {
            $label = '<label for="'.$for.'" ';
            if (count($attr) > 0) {
                foreach ($attr as $key => $val) {
                    $label .= $key.'="'.$val.'" ';
                }
            }
        }
        $this->html .= trim($label).">$content</label>\n";

        return $this;
    }

    public function newline($amount = 1)
    {
        $this->html .= str_repeat("<br/>\n", $amount);

        return $this;
    }

    public function flush()
    {
        $this->html = null;
    }

    public function render()
    {
        echo $this->html."</form>\n";
        $this->flush();
    }
}
