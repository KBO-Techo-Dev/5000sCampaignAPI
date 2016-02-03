<?php
	try {
		$metadata = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' ;
		Zend_Registry::set('metadata', $metadata);
		Zend_Registry::set('self_root', 'http://' . $config->domain . '/' . $config->codename . '/');
		require 'Zend/Controller/Front.php';
		require 'Zend/Controller/Action.php';
		$frontController = Zend_Controller_Front::getInstance();
		$frontController->throwExceptions(true);
		$frontController->setControllerDirectory(APP_ROOT . "$app_folder/controllers");
		$frontController->dispatch();
	} catch (Zend_Exception $e) {
		if ($config->display_runtime_error == 1) echo $e;
	}
