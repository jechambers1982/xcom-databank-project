<?php

use XCOMDatabank\Management\Info;

date_default_timezone_set('America/New_York');
session_start();
	
if($_SERVER["HTTPS"] != "on")
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}

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

global $siteTitle;
$siteTitle = Info::getTitle();