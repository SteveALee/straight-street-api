<?php
/**
 * Define your URI routes here.
 *
 * $route[Request Method][Uri ] = array( Controller class, action method, other options, etc. )
 *
 * RESTful api support, *=any request method, GET PUT POST DELETE
 * POST 	Create
 * GET      Read
 * PUT      Update, Create
 * DELETE 	Delete
 */
$route['*']['/'] = array('HTMLController', 'usage');
$route['*']['/error'] = array('ErrorController', 'index');
$route['*']['/about'] = $route['*']['/'];
$route['*']['/usage'] = $route['*']['/'];

define('VALID_NAME', '/^[a-z][a-z0-9_+ %]+$/i');
define('VALID_NUMBER', '/^\d+$/');

//------ REST api routes -------
$route['*']['/symbol/EN/:name'] = array('APIController', 'className'=>'SymbolController', 'listSymbol',
											 'match'=> array('name'=> VALID_NAME));

$route['*']['/symbols/EN'] = array('APIController', 'className'=>'SymbolController', 'listSymbols');
$route['*']['/symbols/EN/:page'] = array('APIController', 'className'=>'SymbolController', 'listSymbols',
											 'match'=> array('page'=> VALID_NUMBER));
$route['*']['/symbols/EN/:page/:pagesize'] = array('APIController', 'className'=>'SymbolController', 'listSymbols',
													'match'=> array('page'=>VALID_NUMBER, 'pagesize'=>VALID_NUMBER));
$route['*']['/symbols/EN/:name'] = array('APIController', 'className'=>'SymbolController', 'listSymbols',
											 'match'=> array('name'=> VALID_NAME));
$route['*']['/symbols/EN/:name/:page'] = array('APIController', 'className'=>'SymbolController', 'listSymbols', 
												'match'=> array('name'=>VALID_NAME, 'page'=>VALID_NUMBER));
$route['*']['/symbols/EN/:name/:page/:pagesize'] = array('APIController', 'className'=>'SymbolController', 'listSymbols', 
															'match'=> array('name'=> VALID_NAME, 'page'=>VALID_NUMBER, 'pagesize'=>VALID_NUMBER));

$route['*']['/tag/EN/:name'] = array('APIController', 'className'=>'TagController', 'listTag',
											 'match'=> array('name'=> VALID_NAME));

$route['*']['/tags/EN'] = array('APIController', 'className'=>'TagController', 'listTags');
$route['*']['/tags/EN/:page'] = array('APIController', 'className'=>'TagController', 'listTags',
											 'match'=> array('page'=> VALID_NUMBER));
$route['*']['/tags/EN/:page/:pagesize'] = array('APIController', 'className'=>'TagRController', 'listTags',
													'match'=> array('page'=>VALID_NUMBER, 'pagesize'=>VALID_NUMBER));
$route['*']['/tags/EN/:name'] = array('APIController', 'className'=>'TagController', 'listTags',
											 'match'=> array('name'=> VALID_NAME));
$route['*']['/tags/EN/:name/:page'] = array('APIController', 'className'=>'TagController', 'listTags', 
												'match'=> array('name'=>VALID_NAME, 'page'=>VALID_NUMBER));
$route['*']['/tags/EN/:name/:page/:pagesize'] = array('APIController', 'className'=>'TagController', 'listTags', 
															'match'=> array('name'=> VALID_NAME, 'page'=>VALID_NUMBER, 'pagesize'=>VALID_NUMBER));

$route['*']['/api/failed/:msg'] = array('APIController', 'api_fail');         

//Http digest auth with Rest
//$route['post']['/api/admin/dostuff'] = array('APIController', 'admin');

?>