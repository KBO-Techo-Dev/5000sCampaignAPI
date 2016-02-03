<?php
class Utils
{
	public static function extractControllerName ($inAppFolder , $inUri) 
	{
		$request_uri = $inUri;
		$folder_len = strlen($inAppFolder) + 1;
		$request_uri_len = strlen($request_uri);
		$controller_name = '';
		$trim_request_uri = str_replace('/' . $inAppFolder, '', $request_uri);
		if ($request_uri_len == $folder_len) {
			$controller_name = 'index';
		} else if (($first_slash = strpos($trim_request_uri, '/')) !== FALSE) {
			$controller_name = substr($trim_request_uri, 0, $first_slash);		
		} else {
			$controller_name = $trim_request_uri;
		}
		if (substr($controller_name , 0 , 1) == '?') $controller_name = 'index';
		else {
			$uri_section = explode('?' , $controller_name)	;
			if (isset($uri_section[0])) $controller_name = $uri_section[0];
		}
		return $controller_name;
	}
	public static function getRandomChancePool($inPool) {
		$index = 0;
		$sum = 0;
		$sum_array = array();
		foreach($inPool as $key => $p) {
			$sum += $p;
			array_push($sum_array, (($p > 0) ? $sum : 0));			
		}
		$random_number = rand(1, $sum);		
		foreach($sum_array as $key => $p) {
			if ($random_number <= $p && $inPool[$key] > 0) {
				return $key;
			}
		}
		return $index;
	}
	public function str2int($string, $concat = true)
	{
		$length = strlen($string);   
		for ($i = 0, $int = '', $concat_flag = true; $i < $length; $i++) {
			if (is_numeric($string[$i]) && $concat_flag) {
				$int .= $string[$i];
			} elseif(!$concat && $concat_flag && strlen($int) > 0) {
				$concat_flag = false;
			}       
		}
		return (int) $int;
	}
	/**
	* The static method to convert THAI text (UTF8 to TIS-620)
	* Example:
	*   echo HtmlCharacterEntity::thEncode($thaiText);
	* 
	* @param string 
	* @param bool If TRUE, the html special characters should be ingnored 
	*   ( the '<' still '<').
	*   For FALSE, the html special characters will be encoded ( the '<' will be '&lt;' )
	* @return string
	*/
	public static function thEncode($text, $isIgnoreHtmlspecialchars=false)
	{
		if( !$text )
		{
			return '';
		}
		if( !$isIgnoreHtmlspecialchars )
		{
			$text = htmlspecialchars($text);
		}

		$text = @iconv('UTF-8', 'TIS-620', $text);
		
		$len    = strlen($text);
		$result = '';

		for( $i = 0; $i < $len; $i++ )
		{
			$ch     = substr($text, $i, 1);
			$chCode = ord($ch);

			if($chCode < 161 || $ch > 251)
			{
				$result .= $ch;
			}
			else
			{
				$unicode = ord($ch) + 3424;
				$result .= '&#' . $unicode . ';';
			}
		}
		return $result;
	}
	
	public static function getRealIpAddr()
	{
		$ip = 'unknown';
		if (isset($_SERVER['HTTP_CLIENT_IP']) and $_SERVER['HTTP_CLIENT_IP'] != '')   //check ip from share internet
	    {
	      $ip=$_SERVER['HTTP_CLIENT_IP'];
	    }
	    elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) and $_SERVER['HTTP_X_FORWARDED_FOR'] != '' )   //to check ip is pass from proxy
	    {
	      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	    }
	   	elseif (isset($_SERVER['REMOTE_ADDR']) and $_SERVER['REMOTE_ADDR'] != '')
	    {
	      $ip=$_SERVER['REMOTE_ADDR'];
	    }
	    return $ip;
	}
	public static function getBetweenDateTime($inStart, $inEnd, $inNow)
	{
		$diff_start = strtotime($inStart) - strtotime($inNow);
		//echo "diff start: ".$diff_start."<br/>";
		if ($diff_start > 0) return FALSE;
			
		$diff_end = strtotime($inEnd) - strtotime($inNow);
		//echo "diff end: ".$diff_end."<br/>";
		if ($diff_end < 0) return FALSE;
	
		return TRUE;
	}
	public static function getDateBetween($inStartDate, $inEndDate)
	{
		$dates = array();
		if ($inStartDate >= $inEndDate) {
			array_push($dates, $inStartDate);
		} else {
			$newDate = $inStartDate;
			$endDate = strtotime($inEndDate);
			$date = strtotime($newDate);
			while ($date <= $endDate) {
				array_push($dates, date("Y-m-d", $date));
				$newDate = date("Y-m-d", $date)." +1 day";
				$date = strtotime($newDate);
			}
		}
		return $dates;
	}
	public static function getDateDifference($inStartDate = NULL, $inEndDate = NULL)
	{
        $ReturnArray = array();
       
        $SDSplit = explode('-',$inStartDate);
        $StartDate = mktime(0,0,0,$SDSplit[1],$SDSplit[2],$SDSplit[0]);
       
        $EDSplit = explode('-',$inEndDate);
        $EndDate = mktime(0,0,0,$EDSplit[1],$EDSplit[2],$EDSplit[0]);
       
        $DateDifference = $EndDate-$StartDate;
       
        $ReturnArray['YearsSince'] = $DateDifference/60/60/24/365;
        $ReturnArray['MonthsSince'] = $DateDifference/60/60/24/365*12;
        $ReturnArray['DaysSince'] = $DateDifference/60/60/24;
        $ReturnArray['HoursSince'] = $DateDifference/60/60;
        $ReturnArray['MinutesSince'] = $DateDifference/60;
        $ReturnArray['SecondsSince'] = $DateDifference;

        $y1 = date("Y", $StartDate);
        $m1 = date("m", $StartDate);
        $d1 = date("d", $StartDate);
        $y2 = date("Y", $EndDate);
        $m2 = date("m", $EndDate);
        $d2 = date("d", $EndDate);
       
        $diff = '';
        $diff2 = '';
        if (($EndDate - $StartDate)<=0) {
            // Start date is before or equal to end date!
            $diff = "0 days";
            $diff2 = "Days: 0";
        } else {

            $y = $y2 - $y1;
            $m = $m2 - $m1;
            $d = $d2 - $d1;
            $daysInMonth = date("t",$StartDate);
            if ($d<0) {$m--;$d=$daysInMonth+$d;}
            if ($m<0) {$y--;$m=12+$m;}
            $daysInMonth = date("t",$m2);
           
            // Nicestring ("1 year, 1 month, and 5 days")
            if ($y>0) $diff .= $y==1 ? "1 year" : "$y years";
            if ($y>0 && $m>0) $diff .= ", ";
            if ($m>0) $diff .= $m==1? "1 month" : "$m months";
            if (($m>0||$y>0) && $d>0) $diff .= ", and ";
            if ($d>0) $diff .= $d==1 ? "1 day" : "$d days";
           
            // Nicestring 2 ("Years: 1, Months: 1, Days: 1")
            if ($y>0) $diff2 .= $y==1 ? "Years: 1" : "Years: $y";
            if ($y>0 && $m>0) $diff2 .= ", ";
            if ($m>0) $diff2 .= $m==1? "Months: 1" : "Months: $m";
            if (($m>0||$y>0) && $d>0) $diff2 .= ", ";
            if ($d>0) $diff2 .= $d==1 ? "Days: 1" : "Days: $d";
           
        }
        $ReturnArray['NiceString'] = $diff;
        $ReturnArray['NiceString2'] = $diff2;
        return $ReturnArray;
    }
	public static function getDefferentTimestamp ($inStart , $inEnd)
	{
		$temp1  = explode(' ' , $inStart); 
		$temp2  = explode(' ' , $inEnd);
		$start_date  = explode('-' , $temp1[0]);
		$end_date =  explode('-' , $temp2[0]);
		$start_time =  explode(':' , $temp1[1]);
		$end_time = explode(':' , $temp2[1]);
		$start_timestamp = mktime($start_time[0] , $start_time[1] , $start_time[2] ,$start_date[1] , $start_date[2] , $start_date[0] );
		$end_timestamp =  mktime($end_time[0] , $end_time[1] , $end_time[2] ,$end_date[1] , $end_date[2] , $end_date[0] );
		return $end_timestamp - $start_timestamp;
	}
	public static function getFilemtimeRemote($Uri)
	{
	    $Uri = parse_url($Uri);
	    $handle = @fsockopen($Uri['host'],80);
	    if(!$handle)
	        return 0;
	
	    fputs($handle,"GET $Uri[path] HTTP/1.1\r\nHost: $Uri[host]\r\n\r\n");
	    $result = 0;
	    while(!feof($handle))
	    {
	        $line = fgets($handle,1024);
	        if(!trim($line))
	            break;
	
	        $col = strpos($line,':');
	        if($col !== false)
	        {
	            $header = trim(substr($line,0,$col));
	            $value = trim(substr($line,$col+1));
	            if(strtolower($header) == 'last-modified')
	            {
	                $result = strtotime($value);
	                break;
	            }
	        }
	    }
	    fclose($handle);
	    return $result;
	}
	public static function isAssoc($inVar) {
    	return is_array($inVar) && array_diff_key($inVar,array_keys(array_keys($inVar)));
	}
	public static function mkdirRecursive($inPath, $inMode)
	{
		is_dir(dirname($inPath)) || Utils::mkdirRecursive(dirname($inPath), $inMode);
		umask(0);
		return is_dir($inPath) || @mkdir($inPath, $inMode);
	}	
	public static function readXWapProfile ($inUrl) 
	{
		$mobile_data = array ();
		try {
			$remove_str = array('"', ' ', 'X-Wap-Profile: ');
			$x_wap_profile = str_replace( $remove_str, '', $inUrl);
			$ch = curl_init();   	
			curl_setopt($ch, CURLOPT_URL, $x_wap_profile);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 		 				 
			$responseStr = curl_exec($ch); 		
			$dom     = new DOMDocument('1.0', 'UTF-8');	
			$isValid = @$dom->loadXML($responseStr);		
			if ($isValid) {
				$mobile_data['vendor'] = $dom->getElementsByTagName('Vendor')->item(0)->textContent;
				$mobile_data['model'] = $dom->getElementsByTagName('Model')->item(0)->textContent;
			}
		} catch (Exception $e) {}		
		return $mobile_data;
	}
	public static function second2Hour($inSec, $inShowHour = TRUE)
	{
		$hours = intval($inSec / 3600);
		$secs = ($hours > 0) ? $inSec - ($hours * 3600) : $inSec;
		$mins = intval($secs / 60);
		$secs = ($mins > 0) ? $secs - ($mins * 60) : $secs;
		
		if ($hours > 0 or $inShowHour)
			return sprintf("%02d", $hours) . ":" . sprintf("%02d", $mins) . ":" . sprintf("%02d", $secs);
		else
			return sprintf("%02d", $mins) . ":" . sprintf("%02d", $secs);
	}
	public static function trimTag($inText, $inNum)
	{
		$head_text = "";
		$tail_text = "";
		$temp_text = $inText;
	
		$blanket_count = 0;
		
		$count = 0;
		while ($blanket_count < $inNum) {
			$pos1 = strpos($temp_text, '[');
			$pos2 = strpos($temp_text, ']');
			if ( (is_bool($pos1) && !$pos1) || (is_bool($pos2) && !$pos2 ) )
				break;
			if ($pos2 > $pos1)
				$blanket_count++;
			
			$head_text = $head_text . substr($temp_text, 0, $pos2+1) ;	
			$temp_text = substr($temp_text, $pos2+1);
		}
		
		$remain = true;
		while ( $remain ) {
			$pos1 = strpos($temp_text, '[');
			$pos2 = strpos($temp_text, ']');			
			if ( (is_bool($pos1) && !$pos1) || (is_bool($pos2) && !$pos2 ) )
				$remain = false;
				
			if ($remain) {
				if ($pos2 > $pos1)
					$tail_text = $tail_text . substr($temp_text, 0, $pos1);
				else
					$tail_text = $tail_text . substr($temp_text, 0, $pos2);
				
				$temp_text = substr($temp_text, $pos2+1);
			}
			else {
				$tail_text = $tail_text . $temp_text;
			}
		}
		return $head_text . $tail_text;
	}
	public static function addDateTime ($inDateTime , $inAddType , $inAddValue) {
		$result = $inDateTime;
		if ($inAddValue != 0) {
			if ($inAddValue > 0) $sign = '+';
			else $sign = '';
			$text = $sign . ' ' . $inAddValue . ' ' .$inAddType;
			$result = date("Y-m-d G:i:s", strtotime($text , strtotime($inDateTime)));
		}
		return $result;
	}
	public static function verifySignature ($inData , $inSig , $inOption) 
	{
		$token = '';
		$content = json_encode($inData);
		$content = str_replace("\\n", "", $content);
		$content = str_replace("\\t", "", $content);
		$content = str_replace("\\r", "", $content);
		$content = str_replace("\t", "", $content);
		$content = str_replace("\n", "", $content);
		$content = str_replace("\\", "", $content);
		$input_sig = hash_hmac($inOption['algorithm'], $content, $inOption['salt_key']);
		//echo "equal test $inSig:$input_sig \n";
		return ($inSig == $input_sig);
	}
	public static function arrSearch ( $inArray , $inExpression ) 
  	{ 
	    $result = array(); 
	    $inExpression = preg_replace ( "/([^\s]+?)(=|<|>|!)/" , "\$a['$1']$2", $inExpression ); 
	    foreach ( $inArray as $a ) if ( eval ( "return $inExpression;" ) ) $result[] = $a; 
	    return $result; 
   	}   	
	public static function closeHTMLTags ($html, $ignore=array('img', 'hr', 'br')) {
        if (preg_match_all("#<([a-z]+)( .*)?(?!/)>#iU", $html, $opentags)) {
            $opentags[1] = array_diff($opentags[1], $ignore);
            $opentags[1] = array_values($opentags[1]);
            preg_match_all("#</([a-z]+)>#iU", $html, $closetags);
            $opened = count($opentags[1]);
            if (count($closetags[1]) == $opened) return $html;
            $opentags[1] = array_reverse($opentags[1]);
            for ($i=0;$i<$opened;$i++) {
                if (!in_array($opentags[1][$i], $closetags)) $html .= '</'.$opentags[1][$i].'>';
                else unset($closetags[array_search($opentags[1][$i], $closetags)]);
            }
        }
        return $html;
    }
    
    //Encryption function
    public static function rijndaelEncrypt($encrypt, $key, $iv)  
	{  
    	$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');  
    	mcrypt_generic_init($td, $key, $iv);  
    	$encrypted = mcrypt_generic($td, $encrypt);  
    	$encode = base64_encode($encrypted);  
    	mcrypt_generic_deinit($td);  
    	mcrypt_module_close($td);  
    	return $encode;  
	}  

	//Decryption function  
	 public static function rijndaelDecrypt($decrypt, $key, $iv)  
	{  
    	$decoded = base64_decode($decrypt);  
    	$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');  
    	mcrypt_generic_init($td, $key, $iv);  
    	$decrypted = mdecrypt_generic($td, $decoded);  
    	mcrypt_generic_deinit($td);  
    	mcrypt_module_close($td);  
    	return trim($decrypted);  
	}  
	
	public static function getClientInfo ($config , $via_api)
	{
		$temp_client = array (
				'ip'				=>	Utils::getRealIpAddr() ,
				'country_code'		=>	'n/a' ,
				'country_name'		=>	'Unknown' ,
				'region_code'		=>	'n/a' ,
				'region_name'		=>	'Unknown',
				'city'				=>	'Unknown',
				'zip_postal_code'	=>  -1,
				'latitude'			=>	-1,
				'longitude'			=>	-1,
				'timezone'			=>	0,
				'gmt_offset'		=>	-1,
				'dst_offset'		=>	-1,
				'timezone_name'		=>	'n/a',
				'isdst'				=>	-1,
		);
		$client = $temp_client;
		try {
			if($via_api)
			{
				//delegate procedure
				/*
				$client_ip = Utils::getRealIpAddr();
				$api_key = $config->ipinfodb->api->key;
				$api_url = $config->ipinfodb->api->url;
				$ip_xml = @file_get_contents("$api_url?key=$api_key&ip=$client_ip&timezone=true");
				$client_xml = new Zend_Config_Xml ($ip_xml);
				$client = array (
						'ip'				=>	$client_xml->Ip ,
						'country_code'		=>	strtolower($client_xml->CountryCode) ,
						'country_name'		=>	$client_xml->CountryName ,
						'region_code'		=>	strtolower($client_xml->RegionCode) ,
						'region_name'		=>	$client_xml->RegionName,
						'city'				=>	$client_xml->City,
						'zip_postal_code'	=>  $client_xml->ZipPostalCode,
						'latitude'			=>	$client_xml->Latitude,
						'longitude'			=>	$client_xml->Longitude,
						'timezone'			=>	$client_xml->Timezone,
						'gmt_offset'		=>	$client_xml->Gmtoffset,
						'dst_offset'		=>	$client_xml->Dstoffset,
						'timezone_name'		=>	$client_xml->TimezoneName,
						'isdst'				=>	$client_xml->Isdst,
				);*/
				$ip = $temp_client["ip"];
				$ip_xml = @file_get_contents("http://ipinfo.io/{$ip}");
				$details = @json_decode($ip_xml,true);
				$client['country_code'] = isset($details["country"]) ? $details["country"] : 'n/a';
			}	
		} catch (Exception $e) {  $client = array();}
		if(!$client) $client = $temp_client;
		return $client;	
	}
	
	public static function sortArr( $inArr , $inSortBy , $inOption )
	{
		$temp3 = array();
		$max_ingre = count( $inArr );
		for ( $row1 = 0 ; $row1 < ( $max_ingre - 1 ); $row1++){
			for ( $row2 = 0 ; $row2 < ($max_ingre-1) ; $row2++){
				if ( $inOption == 'asc' ){
					if ( $inArr[$row2][$inSortBy] > $inArr[$row2+1][$inSortBy] ){
						$temp3 = $inArr[$row2];
						$inArr[$row2] = $inArr[$row2+1];
						$inArr[$row2+1] = $temp3;
	
					}
				}
				else if ( $inOption == 'desc' ){
					if ( $inArr[$row2][$inSortBy] < $inArr[$row2+1][$inSortBy] ){
						$temp3 = $inArr[$row2];
						$inArr[$row2] = $inArr[$row2+1];
						$inArr[$row2+1] = $temp3;
	
					}
				}
			}
		}
		return $inArr;
	}
	
	public static function resolveTag($inText, $inArray = NULL)
	{
		if ($inArray) {
			$search = array();
			$replace = array();
			foreach ($inArray as $key => $val) {
				$search[] = $key;
				$replace[] = $val;
			}
			return str_replace($search, $replace, $inText);
		}
		return $inText;
	}
	
	public static function sqlInputTest($input)
	{
		$flag				= isset($input["flag"]) ? $input["flag"] : array();
		$order_by 			= isset($input["order_by"]) ? $input["order_by"] : array();
		$order_type			= isset($input["order_type"]) ? $input["order_type"] : array();
		$per_page			= isset($input["per_page"]) ? $input["per_page"] : 0;
		$page_num			= isset($input["page_num"]) ? $input["page_num"] : 0;
		$source				= isset($input["source"]) ? $input["source"] : array();
		$card_ids 			= isset($input["card_ids"]) ? $input["card_ids"] : array();
		$consume_ids		= isset($request_data["consume_ids"]) ? $request_data["consume_ids"] : array();
			
		//validate page_num
		if(!is_int($page_num))
		{
			return false;
		}
			
		//validate per_page
		if(!is_int($per_page))
		{
			return false;
		}
			
		//validate order by
		if(!Utils::validateFlagParam($order_by))
		{
			return false;
		}
			
		//validate order type
		if(!Utils::validateFlagParam($order_type))
		{
			return false;
		}
		
		//validate flag
		if(!Utils::validateFlagParam($flag))
		{
			return false;
		}
		
		//validate soruce
		if(!Utils::validateFlagParam($source))
		{
			return false;
		}
		
		//validate card ids
		if(!Utils::validateFlagParam($card_ids))
		{
			return false;
		}
		
		//validate consume ids
		if(!Utils::validateFlagParam($consume_ids))
		{
			return false;
		}
		return true;
	}
	
	public static function validateFlagParam($inFlag)
	{
		$is_collect = true;
		$counter = 0;
		//injection string
		$array_compare = Utils::getSQLInjection();
		foreach($inFlag as $key => $value)
		{
			foreach($array_compare as $except)
			{
				$temp = explode($except, $key);				
				if( count($temp) > 1 ) {
					$is_collect = false;
				}
				$temp = explode($except,$value );
				if( count($temp) > 1 ) {
					$is_collect = false;
				}
				if(!$is_collect) break;
			}
			if(!$is_collect) break;
		}
		return $is_collect;
	}
	
	public static function getSQLInjection()
	{
		$_array_compare = array(
				' and ' , ' or ' , 'delete' , '(',')' , '+' ,
				'update' , '=' , ' from ' ,
				'grant' , 'insert' , 'alter' , 
				'truncate', 'TRUNCATE',
				'ALTER' ,'GRANT', 'INSERT',
				' AND ' , ' OR ' , 'DELETE' ,
				'UPDATE' , ' FROM ' , '-' , '*' , '/' , '>' , '<'
		);
		return $_array_compare;
	}	
	
	public static function randomString($length = 6) 
	{  
		$str = "";  
		$characters = array_merge(range('A','Z'), range('a','z'), range('1','9'));  
		$max = count($characters) - 1;  
		for ($i = 0; $i < $length; $i++) {   
			$rand = mt_rand(0, $max);  
			$char = $characters[$rand];
			//change 0,o,O to 1
			if($char == "0" || $char == "o" || $char == "O") 
			{
				$char = "1";
			}
			$str .= $char;  
		}  return $str; 
	} 
	
	public static function validateEmail($inStr)
	{
		return filter_var($inStr, FILTER_VALIDATE_EMAIL);
	}
	
	public static function fixedArrayIndex($mixed)
	{
		$result = array();
		foreach ($mixed as $row)
		{
			$result[] = $row;
		}
		return $result;
	}
	
	public static function randomPick($haystack,$pick)
	{
		$result = array();
		$size = count($haystack) - 1;
		while(count($result) < $pick)
		{
			$index = mt_rand(0,$size);
			if(!in_array($haystack[$index],$result))
			{
				$result[] = $haystack[$index];
			}			
		}
		return $result;
	}
	
	public static function getDictSet($inUser,$inGroup,$inSubgroup)
	{
		$db_static 	= Zend_Registry::get("db_static");
		$redict		= array();
		$key 		= "dict_".$inGroup."_".$inSubgroup."_".$inUser->_data['language'];
		if(Zend_Registry::isRegistered($key))
		{
			$redict = Zend_Registry::get($key);
		} else {
			$dict = $db_static->fetchAll("select * from db_dictionary where groups=? and subgroup=?" , array($inGroup,$inSubgroup));
				
			foreach($dict as $index=>$row)
			{
				$redict[$row['codename']] = $row[$inUser->_data['language']];
			}
			Zend_Registry::set($key,$redict);
		}
		return $redict;
	}
	public static function getDictSetByLanguage($inLanguage,$inGroup,$inSubgroup)
	{
		$db_static 	= Zend_Registry::get("db_static");
		$redict		= array();
		$key 		= "dict_".$inGroup."_".$inSubgroup."_".$inLanguage;
		if(Zend_Registry::isRegistered($key))
		{
			$redict = Zend_Registry::get($key);
		} else {
			$dict = $db_static->fetchAll("select * from db_dictionary where groups=? and subgroup=?" , array($inGroup,$inSubgroup));
				
			foreach($dict as $index=>$row)
			{
				$redict[$row['codename']] = $row[$inLanguage];
			}
			Zend_Registry::set($key,$redict);
		}
		return $redict;
	}
}