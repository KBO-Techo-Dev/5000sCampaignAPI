<?php
/* 
 * **********************************************
 * * Tonytoonsz.com : class UserAgent           *
 * **********************************************
 * *                                            *
 * * Developed By : Tonytoons                   *
 * * E-mail       : Tonytoonsz@hotmail.com        *
 * * License      : Tonytoons.                  *
 * *                                            *
 * **********************************************
 */ 
class UserAgent 
{
    protected $_userAgent;
    protected $_uaProf;

	function __construct($_userAgent=null)
    {
        $this->_userAgent = getenv('HTTP_USER_AGENT');
        $this->_uaProf    = $this->_getUaProf();
    }

    function getIt()
    {
    	$_userAgent = $this->_userAgent;
		if (preg_match('|MSIE ([0-9].[0-9]{1,2})|',$_userAgent,$matched)) 
		{
    		$browser_version = $matched[1];
    		$browser         = 'IE';
		} 
		elseif (preg_match( '|Opera/([0-9].+)|',$_userAgent,$matched)) 
		{
    		$browser_version = $matched[1];
    		$browser         = 'Opera';
		} 
		elseif(preg_match('|Firefox/([0-9\.]+)|',$_userAgent,$matched)) 
		{
        	$browser_version = $matched[1];
        	$browser         = 'Firefox';
		}
		elseif(preg_match('|Chrome/([0-9\.]+)|',$_userAgent,$matched)) 
		{
        	$browser_version = $matched[1];
        	$browser         = 'Chrome';
		}
		elseif(preg_match('|BrowserNG/([0-9\.]+)|',$_userAgent,$matched)) 
		{
        	$browser_version = $matched[1];
        	$browser         = 'Nokia';
		}
    	elseif(preg_match("|Nokia|",$_userAgent,$matched)) 
		{
			$temp0 			 = explode(' ' , $_userAgent);
			$temp1 			 = explode('/', $temp0[0]);
			$browser         = $temp1[0];
			$browser_version = $temp1[1];        	
		}
		elseif(preg_match('|BlackBerry([0-9\.]+)|',$_userAgent,$matched)) 
		{
        	$browser_version = $matched[1];
        	$browser         = 'BlackBerry';
		}
		elseif(preg_match('|Safari/([0-9\.]+)|',$_userAgent,$matched)) 
		{
        	$browser_version = $matched[1];
        	$browser         = 'Safari';
			if(preg_match('|iPhone|',$_userAgent,$matched))
			{
				$browser = 'iPhone';
			}
			
		}
		elseif(isset($_SERVER['HTTP_X_WAP_PROFILE']))
		{
			$x_wap_profile = str_replace( '"', '', getenv('HTTP_X_WAP_PROFILE'));					
			$ch = curl_init();   	
			curl_setopt($ch, CURLOPT_URL, $x_wap_profile);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); 				 
			$responseStr = curl_exec($ch); 		
			$dom     = new DOMDocument('1.0', 'UTF-8');	
			$isValid = @$dom->loadXML($responseStr);
			if ($isValid) {
				$browser 	= $dom->getElementsByTagName('Model')->item(0)->textContent;
			}
			else {
				$browser  = 'Other';
			}			
    		$browser_version = 0;    		
		} else {
			// browser not recognized!
    		$browser_version = 0;
    		$browser         = 'Other';
		}
	
		$data = array(
						'browser'         => $browser,
						'browser_version' => $browser_version,
						'userAgent'       => $_userAgent,
					  	'uaProf'          => $this->_uaProf
					  ); //print_r($data);
		return $data;
    }

    protected function _getUaProf()
    {
        if( isset($_SERVER["HTTP_X_WAP_PROFILE"]) )
        {
            return $_SERVER["HTTP_X_WAP_PROFILE"];
        }
        elseif( isset($_SERVER["HTTP_PROFILE"]) )
        {
            return $_SERVER["HTTP_PROFILE"];
        }
        else
        {
            return false;
        }
    }
}

?>