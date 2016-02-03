<?php
class NetworkAPI
{
	var $signature_salt;
	var $aes_key;
	var $aes_iv;
	var $debugging = false;
	public function setConfig($aesKey,$aesIV,$signatureSalt)
	{
		$this->aes_key = $aesKey;
		$this->aes_iv = $aesIV;
		$this->signature_salt = $signatureSalt;
	}
	
	public function isDebuggingMode($debug)
	{
		$this->debugging = $debug;
	}
	
	public function encrypt($data)
	{
		$data = json_encode($data);
		$key = $this->aes_key;
		$iv = $this->aes_iv;
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		mcrypt_generic_init($td, $key, $iv);
		$encrypted = mcrypt_generic($td, $data);
		$encode = base64_encode($encrypted);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $encode;
	}
	
	public function decrypt($data)
	{
		$key = $this->aes_key;
		$iv = $this->aes_iv;
		$decoded = base64_decode($data);
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		mcrypt_generic_init($td, $key, $iv);
		$decrypted = mdecrypt_generic($td, $decoded);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return trim($decrypted);
	}
	
	public function generateSignature($data)
	{
		$content = json_encode($data);
		$content = str_replace("\\n", "", $content);
		$content = str_replace("\\t", "", $content);
		$content = str_replace("\\r", "", $content);
		$content = str_replace("\t", "", $content);
		$content = str_replace("\n", "", $content);
		$content = str_replace("\\", "", $content);
		$input_sig = hash_hmac("md5", $content, $this->signature_salt);
		return $input_sig;
	}
	
	public function call($url,$data)
	{
		$request_data = array();
		$request_data["sig"] = $this->generateSignature($data);
		$request_data["data"] = $data;
		$request_data["rand"] = mt_rand(-99999999, 99999999);
		$encrypted_data = json_encode($request_data);
		//$encrypted_data = $this->encrypt($request_data);
		if($this->debugging)
		{
			echo "\nSent request to $url : \n";
			echo  $encrypted_data;
		}
		
		//set POST variables	
		$fields = array(
			'request' => urlencode($encrypted_data)
		);
		$fields_string = "";
		
		//url-ify the data for the POST
		foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
		$fields_string = substr($fields_string, 0,-1);
		
		//open connection
		$ch = curl_init();
		
		//set the url, number of POST vars, POST data
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_HEADER, FALSE);
		
		//execute post
		$result = curl_exec($ch);
		
		//close connection
		curl_close($ch);
		
		if($this->debugging)
		{
			echo "\nResponse from  $url : \n";
			echo  $result;
		}
		
		//$json_encoded = $this->decrypt($result);
		$response = json_decode($result,true);
		return $response;
	}
}