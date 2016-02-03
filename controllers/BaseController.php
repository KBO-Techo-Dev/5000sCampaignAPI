<?php
require "ReturnStatus.php";
class BaseController extends Zend_Controller_Action
{
	public function init()
	{
		$this->view->start_time 		= microtime (true);
		$db_static 						= Zend_Registry::get('db_static');
		$this->view->metadata 	 		= Zend_Registry::get('metadata');
		$this->view->config 			= Zend_Registry::get('config');	
		$this->view->self_root			= Zend_Registry::get('self_root');
		$this->view->self 				= $this->view->self_root . $this->view->page;
		$this->view->request_uri 		= getenv('REQUEST_URI');
		$this->view->client				= Zend_Registry::get('client');	
		$this->view->controller_name	= Zend_Registry::get('controller_name' );
		$this->view->action				= $this->getRequest()->getActionName();
		$this->view->request_id			= 0;
		$this->view->uid				= 0;
		$request_data = $this->getRequestData();
		// [ADD] by Puttipong Nakapat to allow bypass signature system for DEBUG purpose.
		//if($request_data["status"] != 200) {
		//	$this->sendResponse($request_data);
		//}
		// Specify name of controller that does not require login to connect.
		if(!in_array($this->view->controller_name, array('account', 'info', 'stat')))
		{
			if(!isset($request_data["data"]["token"])) {
					$data = array (
						"data" => null,
						"status" => 305,
						"status_msg" => "[BaseController] Input Wrong Format"
					);
					$this->sendResponse($data);
			} else {
				try {
					$uid = GameManager::getUidByToken($request_data["data"]["token"]);
					if($uid == 0){
						$data = array (
							"data" 			=> null,
							"status" 		=> 302,
							"status_msg" 	=> "[BaseController] Session Not Found"
						);
						$this->sendResponse($data);
					}
					$users = new Users();
					$me = $users->init($uid);
					$authorize = $me->_data["authorize"];
					if($me->_data['status'] == "banned")
					{
						$data = array (
						"data" => null,
						"status" => 601,
						"status_msg" => "[BaseController] Account was banned"
						);
						$this->sendResponse($data);
					}
					if(!$db_static->fetchRow("select * from db_controller where name=? and $authorize='yes'",$this->view->controller_name)) {
							$data = array (
								"data" 			=> null,
								"status" 		=> 404,
								"status_msg" 	=> "[BaseController] API Not Found"
							);
							$this->sendResponse($data);
					}
					$this->view->uid = $me->_data["uid"];
					//$this->rewriteCreatureMax($me);
					Zend_Registry::set('me',$me);
				} catch (Exception $e) {
					$data = array (
							"data" 			=> null,
							"status" 		=> 500,
							"status_msg" 	=> "[BaseController] Internal Server Error:".$e
					);
					$this->sendResponse($data);
				}			
			}
		}
		//checking 
		$this->view->request_data = $request_data;			
		
	}   
	
	public function sendResponse($inResponse)
	{
		$inResponse["execute_time"] =  microtime (true) - $this->view->start_time;
		$inResponse["rand"] = microtime(true) . "";
		$content = json_encode($inResponse);
		
		echo $content;
		// Disable AES
		/*echo Utils::rijndaelEncrypt( 
				$content , 
				$this->view->config->aes_key , 
				$this->view->config->aes_iv
			);*/
		
		// Store profiler		
		$game	= Zend_Registry::get("game");
		$profiler = $game->getObject("Profiler");
		$profiler->dump($this->view->controller_name,$this->_getParam("action"),TRUE);
		exit();
	}
	
	private function getRequestData()
	{
		//Wrong Format
		$exception_ip = true;
		if(strpos( $this->view->config->maintainance_except ,$this->view->client['ip']) === false)
		{
			$exception_ip = false;
		}
		if( ( $this->view->config->maintainance == "yes" ) && !$exception_ip ) {
			$data = array (
				"data" => array(
						'available_time'=>$this->view->config->maintainance_timeout,
						'info_url'=>$this->view->config->maintainance_info
				),
				"status" => ReturnStatus::SERVER_MAINTENANCE,
			);
			return $data;
		}
		if(!isset($_REQUEST["request"])) {
			$data = array (
				"data" => null,
				"status" => 305,
				"status_msg" => "Input Wrong Format [Empty Request]"
			);
			return $data;
		}
		$request = array();
		try {
			$request = json_decode($_REQUEST["request"],true);
			// Disable AES
			/*$request = json_decode(
				Utils::rijndaelDecrypt(
					$_POST["request"],
					$this->view->config->aes_key,
					$this->view->config->aes_iv
				),
				true
			);*/	
		} catch (Exception $e) {
			$data = array (
				"data" => null,
				"status" =>  ReturnStatus::ENCRYPT_FAILED,
			);
			return $data;
		}
		//Wrong Format
		if(!isset($request["data"])) 
		{
			$data = array (
				"data" => null,
				"status" => ReturnStatus::INCORRECT_STRUCTURE,
			);
			return $data;
		}
		if(!isset($request["sig"]))
		{
			$data = array (
				"data" => null,
				"status" => ReturnStatus::INCORRECT_STRUCTURE,
			);
			return $data;
		}
		//verify data
		if(!Utils::verifySignature($request["data"], $request["sig"], array("salt_key"=>$this->view->config->signature_salt,"algorithm" => "md5")))
		{
			$data = array (
				"data" => null,
				"status" => ReturnStatus::ENCRYPT_FAILED,
			);
			return $data;
		}
		
		$request["status"] = 200;
		$request["status_msg"] = "Success";
		return $request;		
	}
	public function sendEmail($inEmail, $inSubject, $inMessage)
	{
		$strHeader = "";
		// HTML version
		if( strpos($inMessage, 'html') > 0 )
		{
			$strHeader .= 'MIME-Version: 1.0' . "\r\n";
			$strHeader .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		}
		$strHeader .= "From: " . $this->view->config->no_reply_email;
		
		$flgSend = @mail($inEmail, $inSubject, $inMessage, $strHeader);
		
		return $flgSend;
	}
}