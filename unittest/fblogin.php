<?php
	$url = "http://".$config->domain . "/" .$config->codename . "/account/fblogin";
	echo $url . "\n";
	$assert_score = 0;
	$total = 1;
	/*Success Case*/
	$register_data = array(
		"auth"	=> "CAAFOcsuoN2IBACF1PvzPlLj9pFbQQ8y0fQZCVN40HC8dEFOq99Yrpgc4pfmkBUC3w2JvTzlvvX9vhC1BEJg4ZAEfF2tmnVTyyUj2Gdi0HMhPN5uGXfuY4NgH9JrI2A9WlGSZBomHGECCjbkYh7JQ6vW2LfaWskXtKcIIQVwwlndO7UbW0apQ4ltZCgcAmFR4IZAX0se9TOHkO4VH15GIt",
	);
	$result = $network_api->call($url, $register_data);
	print_r($result);
	$test = ($result["status"] == 200 );
	if($test) $assert_score++;
	echo "Success case :" . $test . "\n";
	echo "Test Score : " . $assert_score ."/" . $total . "\n";