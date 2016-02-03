<?php 
	$url = "http://".$config->domain . "/" .$config->codename . "/account/login";
	echo $url . "\n";
	$assert_score = 0;
	$total = 2;
	/*Failed Case - Wrong Password*/
	$register_data = array(
		"username"		=> "xxxx@bb.com",
		"password"  	=> "111",
		"first_name" 	=> "a",
		"last_name" 	=> "b"
	);
	$result = $network_api->call($url, $register_data);
	print_r($result);
	$test = ($result["status_msg"] == "Input Wrong Format[Username or Password Wrong]" );
	if($test) $assert_score++;
	echo "Failed Case - Wrong Password :" . $test . "\n";
	/*Success case*/
	$register_data = array(
		"username" 		=> "xsodus000@test.com",
		"password"		=> "123456",
		"first_name" 	=> "a",
		"last_name" 	=> "b"
	);
	$result = $network_api->call($url, $register_data);
	print_r($result);
	$test = ($result["status"] == 200 );
	if($test) $assert_score++;
	echo "Success case :" . $test . "\n";
	print_r($result);
	echo "Test Score : " . $assert_score ."/" . $total . "\n";
?>