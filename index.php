<?php

define('BASE',   implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)));
define('DS',     DIRECTORY_SEPARATOR);
define('ROOT',   str_replace('/\\', DS.DS, rtrim(realpath(__DIR__), '/\\')).DS);
define('SYSTEM', str_replace('/\\', DS.DS, rtrim(ROOT.'system', '/\\')).DS);
define('APP',    str_replace('/\\', DS.DS, rtrim(ROOT.'app', '/\\')).DS);

require_once SYSTEM.'core'.DS.'bootstrap.php';
require_once SYSTEM.'core'.DS.'App.php';
require_once SYSTEM.'core'.DS.'Loader.php';
require_once SYSTEM.'core'.DS.'Controller.php';
require_once SYSTEM.'core'.DS.'Model.php';

// Start
$app = new App();
