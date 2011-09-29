<?php	
/* 
| --------------------------------------------------------------
| 
| Frostbite Framework
|
| --------------------------------------------------------------
|
| Author: 		Steven Wilson
| Copyright:	Copyright (c) 2011, Steven Wilson
| License: 		GNU GPL v3
|
| * You are authorized to change or remove this comment box only
|	in the index.php file.
*/

/* 
| Attempt to automatically determine our site URL and URI
| If this is not working for your site. then you will need
| to manually define the SITE_URL below. 
*/
define('SITE_DIR', dirname( $_SERVER['PHP_SELF'] ));
define('SITE_URL', 'http://'. $_SERVER['HTTP_HOST'] . SITE_DIR);

// Define a smaller Directory seperater and ROOT path
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));

// Define full paths to the APP and System Folders
define('APP_PATH', ROOT . DS . 'application');
define('SYSTEM_PATH', ROOT . DS . 'system');

/*
| Lets speed to core up by manually loading these system files,
| These classes are not extendable, or replacable
*/
require (SYSTEM_PATH . DS . 'core' . DS . 'Common.php');
require (SYSTEM_PATH . DS . 'core' . DS . 'Debug.php');
require (SYSTEM_PATH . DS . 'core' . DS . 'Registry.php');

// Initiate the system start time
$Benchmark = load_class('Benchmark');
$Benchmark->start('system');
 
// Register the Core to process errors with the custom_error_handler method 
set_error_handler( array( 'System\\Core\\Debug', 'php_error_handler' ), E_ALL | E_STRICT );

// Initiate the framework and let it do the rest ;)
$Frostbite = load_class('Frostbite');
$Frostbite->Init();
?>