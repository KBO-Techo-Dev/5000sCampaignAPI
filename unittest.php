<?php
include 'configuration/index.shell.php';
require 'Users.php';
require 'NetworkAPI.php';
if (!empty($argc) && strstr($argv[0], basename(__FILE__)) and isset($argv[1])) {
	$filename = $argv[1];
	$network_api = new NetworkAPI();
	$network_api->setConfig($config->aes_key,$config->aes_iv,$config->signature_salt);
	if(isset($argv[2]))$network_api->isDebuggingMode($argv[2] == "true");
	include "unittest/$filename.php";
}