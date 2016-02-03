<?php
include 'configuration/index.shell.php';
require 'Users.php';
if($config->maintainance == "yes") exit();
if (!empty($argc) && strstr($argv[0], basename(__FILE__)) and isset($argv[1])) {
	$filename = $argv[1];	
	include "automate/$filename.php";
}
?>