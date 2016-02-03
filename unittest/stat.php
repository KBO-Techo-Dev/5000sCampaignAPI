<?php 
	$url = "http://".$config->domain . "/" .$config->codename . "/stat/query";
	echo $url . "\n";
	$assert_score = 0;
	$total = 1;
	/*Failed Case - Password shoter than 4 characters*/
	$input = array(
		'mode'	=> 1,
		'date'	=> '2015-05-28',
	);
	//print_r($input);
	$result = $network_api->call($url, $input);
	print_r($result);
	$test = ($result['status'] == 200 );
	if($test) $assert_score++;
	//echo "Failed Case - Password shoter than 4 characters :" . $test . "\n";	
	echo "Test Score : " . $assert_score ."/" . $total . "\n";
?>