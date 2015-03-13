<?php

function _autoload_class($class) {
    // echo 'dfs';
    $items = explode('\\', $class);
     // var_export($items);
    if (isset($items[2])) {
      $file = dirname(__FILE__) . '/' . $items[1] . '/' . $items[2] . '.php';
    } else if(isset($items[1])) {
      $file = dirname(__FILE__) . '/' . $items[1] . '.php';
    }
		//var_dump(is_file($file));
		//var_dump(is_file('PheanstalkInterface.php'));
		//var_dump(file_exists(dirname(__FILE__) . '/PheanstalkInterface.php'));
		//echo dirname(__FILE__);
    if (isset($file) && is_file($file)) {
      //  echo 'sdf';
        return include($file);
    }
}

spl_autoload_register('_autoload_class');
