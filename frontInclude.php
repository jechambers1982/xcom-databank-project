<?php
// Error lines if I need to display them
//  THESE SHOULD ALWAYS BE COMMENTED OUT UNLESS ACTIVELY DEBUGGING
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

date_default_timezone_set('America/New_York');

// Autoload Classes
spl_autoload_register(function($class) {
    if(strpos($class, 'XCOMDatabank') !== false) {
        $class = substr($class, 12);
        $class = lcfirst($class);
        $class = str_replace('\\','/',$class);

        $path = __DIR__.$class.'.class.php';
        require $path;
    }
});