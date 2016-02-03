<?php 
	$url = "http://".$config->domain . "/" .$config->codename . "/account/register";
	echo $url . "\n";
	$assert_score = 0;
	$total = 4;
	/*Failed Case - Password shoter than 4 characters*/
	$register_data = array(
		"username"		=> "pnakapat@gmail.com",
		"password"  	=> "123",
		"first_name" 	=> "Puttipong",
		"last_name" 	=> "Nakapat"
	);
	$result = $network_api->call($url, $register_data);
	print_r($result);
	$test = ($result["status_msg"] == "Input Wrong Format [Failed to verify input]" );
	if($test) $assert_score++;
	echo "Failed Case - Password shoter than 4 characters :" . $test . "\n";
	/*Failed Case - Username is not an email address pattern*/
	$register_data = array(
		"username"  => "xxsdasd",
		"password"	=> "123456",
		"first_name" 	=> "a",
		"last_name" 	=> "b"
	);
	$result = $network_api->call($url, $register_data);
	print_r($result);
	$test = ($result["status_msg"] == "Input Wrong Format [Failed to verify input]" );
	if($test) $assert_score++;
	echo "Failed Case - Username is not an email address patter :" . $test . "\n";
	/*Failed Case - Missing first name or last name*/
	$register_data = array(
		"username"  => "xxsdasd@rr.com",
		"password"	=> "123456",
	);	
	$result = $network_api->call($url, $register_data);
	print_r($result);
	$test = ($result["status_msg"] == "Input Wrong Format [Missing first name or last name]" );
	if($test) $assert_score++;
	echo "Failed Case - Missing first name or last name :" . $test . "\n";
	/*Success or duplicate*/
	$register_data = array(
		"username"		=> "pnakapat@gmail.com",
		"password"  	=> "123456",
		"first_name" 	=> "Puttipong",
		"last_name" 	=> "Nakapat"
	);
	$result = $network_api->call($url, $register_data);
	print_r($result);
	$test = ($result["status"] == 200 or $result["status"] == 504 );
	if($test) $assert_score++;
	echo "Success or duplicate" . $test . "\n";
	print_r($result);
	echo "Test Score : " . $assert_score ."/" . $total . "\n";
?>