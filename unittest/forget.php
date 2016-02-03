<?php 
	$url = "http://".$config->domain . "/" .$config->codename . "/account/forget";
	echo $url . "\n";
	$assert_score = 0;
	$total = 1;
	/*Failed Case - Password shoter than 4 characters*/
	$input = array(
		'language'	=> 'th',
		'email'		=> 'pnakapat@gmail.com',
	);
	//print_r($input);
	$result = $network_api->call($url, $input);
	print_r($result);
	$test = ($result['status'] == 200 );
	if($test) $assert_score++;
	//echo "Failed Case - Password shoter than 4 characters :" . $test . "\n";	
	echo "Test Score : " . $assert_score ."/" . $total . "\n";
?>