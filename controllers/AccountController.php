<?php
require 'BaseController.php';

class AccountController extends BaseController
{
	public function init()
	{
		$this->view->title = 'Account';
		$this->view->page = 'account';
		parent::init();
	}    
	public function indexAction ()
	{
		 $data = array (
			"data" 			=> null,
			"status" 		=> 404,
			"status_msg" 	=> "API Not Found"
		 );
		 parent::sendResponse($data);
	}
	public function forgetAction()
	{
		$request_data = $this->view->request_data;
		if( isset($request_data['data']['email']) )
		{
			try {
				$db_users = Zend_Registry::get('db_users');
				if( $row = $db_users->fetchRow('SELECT first_name,last_name,password FROM users_001 WHERE username=? LIMIT 1', array($request_data['data']['email'])) )
				{
					$data = array (
						"data" => array(
							'email' => $request_data['data']['email'],
						),
						"status" => 200,
						"status_msg" => "Success"
					);
					$this->view->dict = Utils::getDictSetByLanguage($request_data['data']['language'], 'index', 'account');
					$this->sendEmail(
						$request_data['data']['email'],
						$this->view->dict['FORGET_PASSWORD.SUBJECT'],
						Utils::resolveTag($this->view->dict['FORGET_PASSWORD.CONTENT'], array(
							'[NAME]' => $row['first_name'] . ' ' . $row['last_name'],
							'[PASSWORD]' => $row['password'],
						))
					);
					parent::sendResponse($data);
				}
				else {
					$data = array (
						"data" => null,
						"status" => ReturnStatus::EMAIL_NOT_FOUND,
						"status_msg" => "Email not found."
					);
					parent::sendResponse($data);						
				}
			}
			catch( Exception $e )
			{
				$data = array (
					"data" => null,
					"status" => $e->getCode(),
					"status_msg" => $e->getMessage(),
				);
				parent::sendResponse($data);
			}
		}
		else {
			$data = array (
				"data" => null,
				"status" => 305,
				"status_msg" => "Input Wrong Format[Email Address]"
			);
			parent::sendResponse($data);
		}
	}
	public function registerAction()
	{
		$request_data = $this->view->request_data;
		$db_static = Zend_Registry::get('db_static');
		$db_users = Zend_Registry::get('db_users');
		$db_users_schema = Zend_Registry::get('db_users_schema');
		$game	= Zend_Registry::get("game");
		
		if( !isset($request_data['data']['username']) || !isset($request_data['data']['password']) )
		{
			 $data = array (
					"data" => null,
					"status" => 305,
					"status_msg" => "Input Wrong Format"
			 );
		 	 parent::sendResponse($data);
		}
		try {
			//verify user input
			$request_data['data']['username'] = urldecode($request_data['data']['username']);
			if(!$this->verifyRegisterUserName($db_users,$db_users_schema,$request_data['data']['username'],$request_data['data']['password']))
			{
				$data = array (
						"data" => null,
						"status" => ReturnStatus::INCORRECT_STRUCTURE,
						"status_msg" => "Input Wrong Format [Failed to verify input]"
				);
				parent::sendResponse($data);
			}
			
			$first_name = isset($request_data['data']['first_name']) ? $request_data['data']['first_name'] : "";
			$last_name = isset($request_data['data']['last_name']) ? $request_data['data']['last_name'] : "";
			$this->view->client = isset($request_data['data']['client']) ? $request_data['data']['client'] : $this->view->client;
			if(strlen($first_name) <= 0 or strlen($last_name) <= 0)
			{
				$data = array (
						"data" => null,
						"status" => ReturnStatus::INCORRECT_STRUCTURE,
						"status_msg" => "Input Wrong Format [Missing first name or last name]"
				);
				parent::sendResponse($data);
			}
			
			//get current setting
			$current_setting = GameManager::getRegisterSetting();
			
			try {
				$register_key = hash_hmac("md5",$request_data['data']['username'], $this->view->config->signature_salt) ;
				$new_user = array (
					"username" 			=> $request_data['data']['username'],
					"password" 			=> $request_data['data']['password'],
					"first_name"		=> $first_name,
					"last_name"			=> $last_name,
					"register_key"		=> $register_key,
					"email"	   			=> $request_data['data']['username'] ,
					"online_datetime" 	=> date("Y-m-d G:i:s"),
					"channel"			=> $current_setting->id,
					"country" 			=> $this->view->client["country_code"],
					"language"			=> ($this->view->client["country_code"] == "TH") ? "th" : "en",
					"year_of_birth"		=> $request_data['data']['year_of_birth'] >= 1900 ? $request_data['data']['year_of_birth'] : 1900,
					"nationality"		=> $request_data['data']['nationality'],
					"media_code"		=> isset($request_data['data']['media_code'])? $request_data['data']['media_code'] : "native",
					"date"				=> date("Y-m-d"),
					"time"  			=> date("G:i:s")
				);
				$db_users->insert($current_setting->usertable , $new_user);
			} catch (Exception $e) {
				$response = array (
						"data" 			=>null,
						"status" 		=> 502,
						"status_msg" 	=> "Duplicate user"
				);
				parent::sendResponse($response);
			}
			
			$users = new Users();
			$me = $users->login($new_user['username'],$new_user['password']);
		
			$response = $this->xSdkafterLoginLogic($me, $game, $request_data["data"]);
			$this->view->dict = Utils::getDictSet($me, "index", "account");
			$this->sendEmail(
				$me->_data['username'],
				$this->view->dict['REGISTER.SUBJECT'],
				Utils::resolveTag($this->view->dict['REGISTER.CONTENT'], array(
					'[NAME]' 		=> $me->_data['first_name'] . ' ' . $me->_data['last_name'],
					'[COUNTRIES]'	=> 7,
				))
			);
			parent::sendResponse($response);			
		} catch (Exception $e)	{
			$data = array (
				"data" 			=> null,
				"status" 		=> 500,
				"status_msg" 	=> "Internal Server Error:".$e
			);
			parent::sendResponse($data);
		}	
	}
	
	public function loginAction()
	{
		$request_data 		= $this->view->request_data;
		$db_static 			= Zend_Registry::get('db_static');
		$game				= Zend_Registry::get("game");
		$username 			= isset($request_data['data']['username']) ? $request_data['data']['username'] : "" ;
		$password 			= isset($request_data['data']['password']) ? $request_data['data']['password'] : "" ;
		$username 			= urldecode($username);
		if( !GameManager::verifyNewUserChar($username, $password)  ){
			 $data = array (
					"data" => null,
					"status" => 305,
					"status_msg" => "Input Wrong Format[Username or Password Wrong]"
			 );
		 	 parent::sendResponse($data);
		}
		try {			
			$users = new Users();			
			try{
				$me = $users->login($username,$password);
				if( !$me->_data  )
				{
					$data = array (
							"data" => null,
							"status" => 502,
							"status_msg" => "User Not Found"
					 );
				 	 parent::sendResponse($data);
				}
				if($me->_data['status'] == 'banned')
				{
					//checked
					$data = array (
							"data" => null,
							"status" => 601,
							"status_msg" => "Account was banned"
					);
					parent::sendResponse($data);
				}				
			}
			catch (Exception $e)
			{
				//passed
				$data = array (
						"data" => null,
						"status" => $e->getCode(),
						"status_msg" => $e->getMessage()
				);
				parent::sendResponse($data);
			}
			
			
			$response = $this->xSdkafterLoginLogic($me, $game, $request_data['data']);
			
			parent::sendResponse($response);
		} catch (Exception $e) {
			$data = array (
				"data" 			=> null,
				"status" 		=> 500,
				"status_msg" 	=> "Internal Server Error:".$e,				
			);
			parent::sendResponse($data);
		}
	}
	private function verifyRegisterUserName($db_users , $db_users_schema , $username,$password)
	{
		//validate characters
		$success = false;			
		$success = GameManager::verifyNewUserChar($username,$password);
		if(!$success) return $success;
		
		//check duplicate username
		$duplicate = GameManager::checkUserDuplicate($username,$db_users, $db_users_schema);			
		if($duplicate) {
			//passed
			$response = array (
					"data" 			=> null,
					"status" 		=> ReturnStatus::DUPLICATED_USER,
					"status_msg" 	=> "Duplicate user"
			);
			parent::sendResponse($response);
		}		
		return !$duplicate;
	}	
	
	public function  fbloginAction ()
	{
		$request_data 		= $this->view->request_data["data"];
		$auth 				= isset($request_data["auth"]) ? $request_data["auth"] : '';
		$media_code			= isset($request_data["media_code"]) ? $request_data["media_code"] : 'native';
		$db_static 			= Zend_Registry::get('db_static');
		$facebook 			= Zend_Registry::get('facebook');
		$db_users 			= Zend_Registry::get('db_users');		
		$db_users_schema 	= Zend_Registry::get('db_users_schema');
		$game				= Zend_Registry::get("game");
		
		try {
			$data = array (
					"data" => null,
					"status" => 305,
					"status_msg" => "Input Wrong Format [MISSING AUTHENTICATION]"
			);
			
			if(!$auth)
			{
				$data["status_msg"] = "Input Wrong Format [MISSING AUTHENTICATION]";
				parent::sendResponse($data);
			}
			
			$auth = urldecode($auth);
			//initial fb auth token
			$facebook->setAccessToken($auth);
			$result = $facebook->api('/me','GET');
			
			//check graph api result
			if(!$result)
			{
				//passed
				$data["status_msg"] = "Input Wrong Format [NOT FOUND FB DATA]";
				parent::sendResponse($data);
			}
			
			if(!isset($result['email']))
			{
				if(isset($result['id']) and $result['id'] > 0)
				{
					if($old_account = GameManager::findUserFromFBID($result['id'],$db_users,$db_users_schema))
					{
						$result['email'] = $old_account['username'];
					} else {
						$result['email'] = $result['id'] . "@facebook.com";
					}
				} else {
					//passed
					$data["status_msg"] = "Input Wrong Format [NOT FOUND EMAIL]";
					parent::sendResponse($data);
				}
			}
			
			//initial username & password
			$username = $result['email'];
			$password = Utils::randomString(GameManager::PASSWORD_MAXIMUM_LENGTH);
			/*
			$year_of_birth = strlen($result['birthday']) == 4 ? $result['birthday'] : '';
			if( $year_of_birth == '' )
			{
				$birthday = explode('/', $result['birthday']);
				if( isset($birthday[2]) )
					$year_of_birth = $birthday[2];
				else
					$year_of_birth = 1900;
			}
			*/
			$year_of_birth	= 1900;
			$nationality	= $result['locale'];
			if( $nationality == 'th_TH' )
				$nationality = 'Thailand';
			else if( $nationality == 'en_US' )
				$nationality = 'United States';
			
			$register_key = hash_hmac("md5",$result['email'], $this->view->config->signature_salt) ;
			
			$new_user = array (
				"username" 			=> $username,
				"password" 			=> $password,
				"first_name"		=> $result['first_name'],
				"last_name"			=> $result['last_name'],
				"register_key"		=> $register_key,
				"facebook_id"		=> $result['id'] ,
				"email"	   			=> $result['email'] ,
				"online_datetime" 	=> date("Y-m-d G:i:s"),
				"channel"			=> 0,
				"language"			=> ($this->view->client["country_code"] == "TH") ? "th" : "en",
				"country" 			=> $this->view->client["country_code"],
				"year_of_birth"		=> $year_of_birth,
				"nationality"		=> $nationality,
				"media_code"		=> $media_code,
				"date"				=> date("Y-m-d"),
				"time"  			=> date("G:i:s")
			);
			
			//check login
			$found_acc = GameManager::getFBUserData($username, $db_users, $db_users_schema);			
			$me = null;
			
			if($found_acc)
			{
				$this->xSdkFoundAccountLogin($me,$game,$found_acc);
			}
			else {
				//verify new user
				if(!$this->verifyRegisterUserName($db_users, $db_users_schema, $username, $password))
				{
					$data["status_msg"] = "Input Wrong Format [Verify Failed]";
					parent::sendResponse($data);
				}
				
				//get current setting
				$current_setting = GameManager::getRegisterSetting();
				
				//insert new user record
				try {
					$new_user['channel'] = $current_setting->id;
					$db_users->insert($current_setting->usertable , $new_user);
				} catch (Exception $e) {
					//passed
					$response = array (
							"data" 			=> null,
							"status" 		=> ReturnStatus::DUPLICATED_USER,
							"status_msg" 	=> "Duplicate user"
					);
					parent::sendResponse($response);
				}
					
				$users = new Users();
				$me = $users->login($new_user["username"],$new_user["password"]);
				$this->view->dict = Utils::getDictSet($me, "index", "account");
				$this->sendEmail(
					$me->_data['username'],
					$this->view->dict['REGISTER.SUBJECT'],
					Utils::resolveTag($this->view->dict['REGISTER.CONTENT'], array(
						'[NAME]' 		=> $me->_data['first_name'] . ' ' . $me->_data['last_name'],
						'[COUNTRIES]'	=> 7,

					))
				);
			}
			$response = $this->xSdkafterLoginLogic($me,$game,$request_data);
			
			parent::sendResponse($response);
		} catch (Exception $e) {
			$data = array (
					"data" 			=> null,
					"status" 		=> ReturnStatus::INTERNAL_SERVER_ERROR,
					"status_msg" 	=> "Internal Server Error:".$e,
			);
			parent::sendResponse($data);
		}		
	}	
	
	//It use for x-sdk login
	private function xSdkFoundAccountLogin(&$me,$game,$found_acc) {
		$users = new Users();
		$me = $users->login($found_acc['username'],$found_acc['password']);
		
		//check account banned
		if($me->_data['status'] == 'banned')
		{
			$data = array (
					"data" => null,
					"status" => ReturnStatus::ACCOUNT_WAS_BANNED,
					"status_msg" => "Account was banned"
			);
			parent::sendResponse($data);
		}
		
	}
	
	//Other logic after logined
	private function xSdkafterLoginLogic ($me,$game,$request_data)
	{
		//create session and token
		$session = GameManager::createSession($me);
		$client = Zend_Registry::get("client");
		//save device info
		if(isset($request_data['system_info']))
		{
			GameManager::storeDeviceInfo($me, $request_data['system_info']);
		}
			
		//update country_code
		if($client["country_code"] != $me->_data["country"])
		{
			$me->updateUser(array("country" => $client["country_code"]));
		}
		
		//update fb token
		if(isset($request_data["auth"]))
		{
			if($request_data["auth"] != $me->_data["fb_token"])
			{
				$me->updateUser(array("fb_token" => $request_data["auth"]));
			}
		}
		
		unset($me->_data['password']);
		$response = array (
				"data" => array (
					"user" 	=> $me->_data,
					"token"	=> $session["token"],
				),
				"status" 		=> 200,
				"status_msg" 	=> "Success",
		);
		return $response;
	}	
	public function infoAction()
	{
		$request_data 		= $this->view->request_data["data"];
		$token 				= isset($request_data['token']) ? $request_data['token'] : "";
		try {
			$uid = GameManager::getUidByToken($token);
			$me = GameManager::getUserObject($uid);
			
			$response = array (
					"data" 			=> $me->_data,
					"status" 		=> ReturnStatus::SUCCESS,
					"status_msg" 	=> "Success",
			);
			
			parent::sendResponse($response);
		} catch (Exception $e) {
			$data = array (
					"data" 			=> null,
					"status" 		=> ReturnStatus::INTERNAL_SERVER_ERROR,
					"status_msg" 	=> "Internal Server Error:".$e,
			);
			parent::sendResponse($data);
		}
	}
}