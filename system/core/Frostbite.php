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
| * USE THIS FILE AS THE BOOTSTRAP *
|
*/
namespace System\Core;

class Frostbite
{
	public $Router;
	protected $dispatch;
	
/*
| ---------------------------------------------------------------
| Method: Init()
| ---------------------------------------------------------------
|
| This is the function that runs the whole show!
|
| @Return: (None)
|
*/
	function Init()
	{
		// Initialize the router
		$this->Router = load_class('Core\\Router');
		
		// Tell the router to process the URL for us
		$routes = $this->Router->routeUrl();
		
		// Initialize some important routing variables
		$controller   = $GLOBALS['controller']   = $routes['controller'];
		$action       = $GLOBALS['action']       = $routes['action'];
		$queryString  = $GLOBALS['queryString']  = $routes['queryString'];
	
		// -----------------------------------------
		// Lets include the application controller.|
		// -----------------------------------------		
		if( !$this->loadApplication() )
		{
			show_404();
		}
		
		// -------------------------------------------------------------
		// Here we init the actual controller / action into a variable.|
		// -------------------------------------------------------------
		$this->dispatch = new $controller();
		
		// After loading the controller, make sure the method exists, or we have a 404
		if(method_exists($controller, $action)) 
		{
			// -------------------------------------------------------------------------
			// Here we call the contoller's before, requested, and after action methods.|
			// -------------------------------------------------------------------------
		
			// Call the beforeAction method in the controller.
			$this->performAction($controller, "_beforeAction", $queryString);
			
			// HERE is where the magic begins... call the Main APP Controller and method
			$this->performAction($controller, $action, $queryString);
			
			// Call the afterAction method in the controller.
			$this->performAction($controller, "_afterAction", $queryString);

		} 
		else 
		{
			// If the method didnt exist, then we have a 404
			show_404();
		}
	}
	
/*
| ---------------------------------------------------------------
| Method: performAction()
| ---------------------------------------------------------------
|
| @Param: (String) $controller - Name of the controller being used
| @Param: (String) $action - Action method being used in the controller
| @Param: (String) $queryString - The query string, basically params for the Action
| @Return: (Object) - Returns the method
|
*/
	
	function performAction($controller, $action, $queryString = null) 
	{	
		if(method_exists($controller, $action)) 
		{
			return call_user_func_array( array($this->dispatch, $action), $queryString );
		}
		return FALSE;
	}
	
/*
| ---------------------------------------------------------------
| Method: loadApplication()
| ---------------------------------------------------------------
|
| Checks the controller and Module folders for a the controller
| and then loads them
|
| @Return: (Bool) - If the controller exists, it returns TRUE
|
*/
	function loadApplication()
	{
		// Make this a bit easier
		$name = $GLOBALS['controller'];
		
		// Check the App controllers folder
		if(file_exists(APP_PATH . DS . 'controllers' . DS . strtolower($name) . '.php')) 
		{
			$GLOBALS['is_module'] = FALSE;
			include (APP_PATH . DS . 'controllers' . DS . strtolower($name) . '.php');
			return TRUE;
		}
		
		// Check the App modules folder
		elseif(file_exists(APP_PATH . DS . 'modules' . DS . strtolower($name) . DS . 'controller.php'))
		{
			$GLOBALS['is_module'] = TRUE;
			include (APP_PATH . DS . 'modules' . DS . strtolower($name) . DS . 'controller.php');
			return TRUE;
		}
		
		// Neither exists, then no controller found.
		return FALSE;
	}
}
// EOF