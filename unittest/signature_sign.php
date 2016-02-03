<?php 
	$url = "http://".$config->domain . "/" .$config->codename . "/signature/sign";
	echo $url . "\n";
	$assert_score = 0;
	$total = 1;
	/*Failed Case - Password shoter than 4 characters*/
	$input = array(
		'token'			=> '9fcc796c25809525485d424b68b45077',
		'campaign_id'	=> 2,
		'uid'			=> 26,
		'comment'		=> 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
		'fb_share'		=> 'no',
		'show_name'		=> 'yes',
	);
	//print_r($input);
	$result = $network_api->call($url, $input);
	print_r($result);
	$test = ($result['status'] == 200 );
	if($test) $assert_score++;
	//echo "Failed Case - Password shoter than 4 characters :" . $test . "\n";	
	echo "Test Score : " . $assert_score ."/" . $total . "\n";
?>