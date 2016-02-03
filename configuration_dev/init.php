<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// PHP common configurations & path settings
header('Cache-Control: no-cache');
session_start();

// Change this variable to your web application directory relative to APP_ROOT
$dir_array = explode('/', getcwd());
$app_folder = $dir_array[count($dir_array)-1]; 
$configuration_folder = 'configuration';
define('APP_FOLDER', $app_folder . '/');
define('ABSOLUTE_PATH', '/var/www/kreatorian/' . APP_FOLDER);
define('APP_ROOT', $_SERVER['DOCUMENT_ROOT'] . '/'); // Change this variable to absolute path (/var/...) when execute by shell
define('CONFIG_FOLDER', $configuration_folder . '/');
set_include_path(
	'.' . PATH_SEPARATOR .
	APP_ROOT . "$app_folder/framework/" . PATH_SEPARATOR .
	APP_ROOT . "$app_folder/gamemaker/" . PATH_SEPARATOR .
	APP_ROOT . "$app_folder/libs" . PATH_SEPARATOR .
	get_include_path()
);

// Zend Framework utility classes.
require 'Zend/Loader.php';
require 'Zend/Config/Ini.php';
require 'Zend/Registry.php';
require 'Zend/Config/Xml.php';
require 'Zend/Log/Writer/Stream.php';
require 'Zend/Log.php';
require 'facebook-php-sdk-master/src/facebook.php';

$config = new Zend_Config_Ini(APP_ROOT . APP_FOLDER  . CONFIG_FOLDER . 'general_config.ini', 'production'); // Load general_config.ini from root.
Zend_Registry::set('config', $config);
$channel_config = new Zend_Config_Ini(APP_ROOT . APP_FOLDER  . CONFIG_FOLDER . 'channel_config.ini', 'channel'); // Load channel_config.ini from root.
Zend_Registry::set('channel_config', $channel_config);
($config->display_compile_error==1)? error_reporting(E_ALL) : error_reporting(0);
//date_default_timezone_set($config->timezone);
ini_set('display_startup_errors', $config->display_runtime_error);
ini_set('display_errors', $config->display_runtime_error);
// Initial Objects ////////////////////////////////////////////////////////////////////////////////////////////////////////
	require 'GameManager.php';	
	require 'Users.php';
	require 'gamemaker/gfw/Utils.php';
	$game = new GameManager();		
	$utils = new Utils();
	Zend_Registry::set('game', $game);	
	$controller_name = Utils::extractControllerName(APP_FOLDER, getenv('REQUEST_URI'));
	$facebook = new Facebook(array(
			'appId'  => $config->facebook_app_id,
			'secret' => $config->facebook_app_secret,
	));
	
	Zend_Registry::set('facebook', $facebook);
	
// MEMCACHE SETUP  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Setup memcache if enabled.
//if ($config->memcahce_enable == 'yes') {	
	
	require 'Zend/Cache.php';
	$frontendOptions = array(
	   'lifetime' => $config->memcache->frontend->lifetime->days,
	   'automatic_serialization' => true
	);
	$backendOptions = array(
		'servers' => array(
			array(
			  'host' => $config->memcache->servers->ip,
			  'port' => $config->memcache->servers->port, 
			  'persistent' => true
			)
		),
		'compression' => false
	);
	try {
		$cache = Zend_Cache::factory('Core', 'Memcached', $frontendOptions, $backendOptions);
		Zend_Registry::set('cache', $cache);
		$cache_hour = Zend_Cache::factory(
			'Core',
			'Memcached', 
			array(
			   'lifetime' => $config->memcache->frontend->lifetime->hours,
			   'automatic_serialization' => true			
			),
			$backendOptions
		);
		Zend_Registry::set('cache_hour', $cache_hour);
		$cache_minute = Zend_Cache::factory(
			'Core',
			'Memcached', 
			array(
			   'lifetime' => $config->memcache->frontend->lifetime->minutes,
			   'automatic_serialization' => true			
			),
			$backendOptions
		);
		Zend_Registry::set('cache_minute', $cache_minute);
		$cache_output = Zend_Cache::factory('Output', 'Memcached', $frontendOptions, $backendOptions);
		Zend_Registry::set('cache_output', $cache_output);
	} catch (Zend_Exception $e) {	}
//}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// If call existing module's name (?module=[name]) then call that module instead of normally start Zend Framwork
if (isset($_REQUEST['module'])) {
	if (file_exists(ABSOLUTE_PATH . $_REQUEST['module'] . '.php')) {
		include $_REQUEST['module'] . '.php';
	}
	else {
		echo 'Module "' . $_REQUEST['module'] . '" does not exists.';
	}
} else {
		
	// Connect to DB to validate username & password
	// Notes: *** NOT IMPLEMENT YET ***
	//    Configuration of DB users/game is divided into location.
	//    They will point to appropiate server location with "load balance" logic.
	require 'Zend/Db.php';
	
	try {
		$db_static = Zend_Db::factory($config->db->static);
		$db_static->getConnection()->exec("SET NAMES UTF8");
		$db_static->getProfiler()->setEnabled($config->db->static->params->profiler);
		$db_static->setFetchMode(Zend_Db::FETCH_ASSOC);
		Zend_Registry::set('db_static', $db_static);
	} catch (Zend_Exception $e) {echo $e; }
	
	try {
		$db_share = Zend_Db::factory($config->db->share);
		$db_share->getConnection()->exec("SET NAMES UTF8");
		$db_share->getProfiler()->setEnabled($config->db->share->params->profiler);
		$db_share->setFetchMode(Zend_Db::FETCH_ASSOC);
		Zend_Registry::set('db_share', $db_share);
	} catch (Zend_Exception $e) { echo $e;}
	
	try {
		$db_users = Zend_Db::factory($config->db->users);
		$db_users->getConnection()->exec("SET NAMES UTF8");
		$db_users->getProfiler()->setEnabled($config->db->users->params->profiler);
		$db_users->setFetchMode(Zend_Db::FETCH_ASSOC);
		Zend_Registry::set('db_users', $db_users);
	} catch (Zend_Exception $e) { echo $e;}
	
	try {
		$db_users_schema = Zend_Db::factory($config->db->users_schema);
		$db_users_schema->getConnection()->exec("SET NAMES UTF8");
		$db_users_schema->getProfiler()->setEnabled($config->db->users_schema->params->profiler);
		$db_users_schema->setFetchMode(Zend_Db::FETCH_ASSOC);
		Zend_Registry::set('db_users_schema', $db_users_schema);
	} catch (Zend_Exception $e) {echo $e; }
//SESSION MANAGEMENT ////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Clear Session
	if ( isset($_REQUEST['logout']) and $_REQUEST['logout'] == 1) {
		session_unset();
		$dialog_url = "https://" . $config->domain  . '/' . $config->main_dir . '/?force_login=true';	
		if(isset($_REQUEST['develop']) and $_REQUEST['develop'] == 1) {
			$dialog_url .= '&develop=1';
		}
		if(isset($_REQUEST['mcode']))$_SESSION[$config->codename . 'mcode'] = $_REQUEST['mcode'];
		header( 'Location: '.$dialog_url.'' );
		exit();	
	}
	if (isset($_REQUEST['develop']) and $_REQUEST['develop'] == 1){	
		$_SESSION[$config->codename . 'login_type'] = 'developer';
	}
//load ip's description		
	$client = array("ip" => Utils::getRealIpAddr() , "country_code" => "N/A");		
	if(isset($_SESSION['client'])) {
		$client = $_SESSION['client'];
	}	
	else { 		
		$filter = array('account');
		if(in_array($controller_name,$filter))
		{
			$client = Utils::getClientInfo($config,($config->ipinfodb->enable == "yes"));		
			$client['x_wap_profile'] = isset($_SERVER['HTTP_X_WAP_PROFILE']) ? getenv('HTTP_X_WAP_PROFILE') : 'none';
			$client['ua']			 = getenv('HTTP_USER_AGENT');	
			$client['mobile_data']	 = array();		
			if($client['x_wap_profile'] and ($client['x_wap_profile'] != 'none')) {
				$client['mobile_data'] = Utils::readXWapProfile($client['x_wap_profile']);	
			}			
		}
	}		
	$_SESSION['client']  = $client;
	Zend_Registry::set('game', new GameManager());
	Zend_Registry::set('client', $client);
	Zend_Registry::set('controller_name' , $controller_name);
//LOADING USER DATA AND FRAMEWORK /////////////////////////////////////////////////////////////////////////////////	
	include 'framework.php';
}