<?php
class GameManager
{
	const UA_CLASS_WEB = 1;
	const UA_CLASS_HIGH = 2;
	const UA_CLASS_MIDDLE = 3;
	const UA_CLASS_LOW = 4;
	const SESSION_EXPIRE = "+1 day";
	const TOTAL_SECOND_PER_DAY = 86400;
	var $_included;

	public function __construct() { $this->_included = array();}
	public static function getNonFinalChargeResult() { 	return array(100, 101, 102, 103); }
	public function getObject($inName)
	{
		$object = '';
		try {
			$object = Zend_Registry::get($inName);
		} catch (Exception $e) {
			if (!isset($this->_included[$inName])) {
				$this->_included[$inName] = TRUE;
				require $inName . '.php';
			}
			$object = new $inName;
			Zend_Registry::set($inName, $object);
		}
		return $object;
	}
	
	public static function getUserObject($inUid)
	{
		$users = new Users();
		$me = $users->init($inUid);
		return $me;
	} 

	public static function getUidByToken($inToken)
	{
		$uid = 0;
		$db_share = Zend_Registry::get('db_share');
		$db_static = Zend_Registry::get('db_static');
		//no expire datetime
		$session = $db_share->fetchRow("select * from session_pool where token=? " , $inToken);
		if($session==null) $uid = 0;
		else {
			$uid = $session["uid"];
		}
		return $uid;
	}
	
	public static function getUserDataByUsrPwd($inUserName,$inPassword,$inDbConn,$inSchemaConn)
	{
		$result = array();		
		$user_tables = $inSchemaConn->fetchCol("SELECT TABLE_NAME FROM `TABLES` WHERE TABLE_NAME LIKE '%users%'");
		$sql = "";
		$condition = array();
		foreach($user_tables as $table_name)
		{
			$sql .= "select * from " . $table_name . " where username=? and password=? union ";
			$condition[] = $inUserName;
			$condition[] = $inPassword;
		}
		$sql = substr($sql, 0 , -6);
		$result = $inDbConn->fetchRow($sql,$condition);
		return $result;
	}
	
	public static function getUserDataByUid($inUid , $inDbConn , $inSchemaConn) 
	{
		$result = array();
		$user_tables = $inSchemaConn->fetchCol("SELECT TABLE_NAME FROM `TABLES` WHERE TABLE_NAME LIKE '%users%'");
		$sql = "";
		$condition = array();
		foreach($user_tables as $table_name)
		{
			$sql .= " select * from ". $table_name . " where uid=? union ";	
			$condition[] = $inUid;
		}
		$sql = substr($sql, 0 , -6);
		$result = $inDbConn->fetchRow($sql,$condition);
		return $result;
	}
	
	public static function checkUserDuplicate($inUserName,$inDbConn,$inSchemaConn)
	{
		$result = array();
		$user_tables = $inSchemaConn->fetchCol("SELECT TABLE_NAME FROM `TABLES` WHERE TABLE_NAME LIKE '%users%'");
		$sql = "";
		$condition = array();
		foreach($user_tables as $table_name)
		{
			$sql .= "select * from " . $table_name . " where username=? union ";
			$condition[] = $inUserName;
		}
		$sql = substr($sql, 0 , -6);
		$result = $inDbConn->fetchRow($sql,$condition);
		if($result) return true;
		else return false;
	}	
	
	const USERNAME_MINIMUM_LENGTH = 6;
	const USERNAME_MAXIMUM_LENGTH = 256;
	const PASSWORD_MINIMUM_LENGTH = 4;
	const PASSWORD_MAXIMUM_LENGTH = 12;
	public static function verifyNewUserChar($inUserName,$inPassword)
	{
		//verify email in pattern abc@home.com
		if(filter_var($inUserName, FILTER_VALIDATE_EMAIL)) $result = true;
		else $result = false;
		
		$result = $result && preg_match("/^[A-Za-z0-9]*$/",$inPassword);	
		//check username length
		$result = (($result) && (strlen($inUserName) >= GameManager::USERNAME_MINIMUM_LENGTH) && (strlen($inUserName) <= GameManager::USERNAME_MAXIMUM_LENGTH));
		//check password length
		$result = (($result) && (strlen($inPassword) >= GameManager::PASSWORD_MINIMUM_LENGTH) && (strlen($inPassword) <= GameManager::PASSWORD_MAXIMUM_LENGTH));
		return $result;		
	}
	
	public static function getDbConnection($config,$dbName)
	{
		$connection = null;
		if(Zend_Registry::isRegistered($dbName))
		{
			$connection = Zend_Registry::get($dbName);			
		} 
		else 
		{
			$connection = Zend_Db::factory($config->database);					
			Zend_Registry::set($dbName, $connection);
		}		
		$connection->getConnection()->exec("SET NAMES UTF8");
		$connection->getProfiler()->setEnabled(true);
		$connection->setFetchMode(Zend_Db::FETCH_ASSOC);
		return $connection;
	}
	
	public static function getCacheName($inName) {
		$config = Zend_Registry::get('config');
		return $config->cache_prefix . $config->version . $inName;
	}
	
	// use with x-sdk login only!!!	
	public static function getFBUserData($inUserName,$inDbConn,$inSchemaConn)
	{
		$result = array();
		$user_tables = $inSchemaConn->fetchCol("SELECT TABLE_NAME FROM `TABLES` WHERE TABLE_NAME LIKE '%users%'");
		$sql = "";
		$condition = array();
		foreach($user_tables as $table_name)
		{
			$sql .= "select * from " . $table_name . " where username = ? union ";
			$condition[] = $inUserName;
		}
		$sql = substr($sql, 0 , -6);
		$result = $inDbConn->fetchRow($sql,$condition);
		return $result;
	}
	
	public static function createSession($inUser)
	{
		$config = Zend_Registry::get('config');
		$db_share = Zend_Registry::get('db_share');	
		if($session = $db_share->fetchRow("select * from session_pool where uid=?",$inUser->_data["uid"]))
		{
			$db_share->insert("session_log",$session);
			$db_share->delete("session_pool",array("id=?"=>$session["id"]));
			$session = array();
		}
		if(!$session)
		{
			$suffixSig = sprintf("%06d",rand(0,999999));
			$token_data = $inUser->_data["uid"]."-".date("Y-m-d G:i:s")."-".$suffixSig;
			$session = array (
					"uid" 				=> $inUser->_data["uid"],
					"token" 			=> hash_hmac("md5",$token_data,$config->signature_salt),
					"date"				=> date("Y-m-d"),
					"time"				=> date("G:i:s"),
					"create_datetime" 	=> date('Y-m-d G:i:s'),
					"expire_datetime" 	=> date('Y-m-d G:i:s',strtotime(GameManager::SESSION_EXPIRE))
			);
			$db_share->insert("session_pool" , $session );
		}
		return  $session;		
	}
	
	public static function findUserFromFBID ($inFBID,$inDbConn , $inSchemaConn)
	{
		$result = array();
		$user_tables = $inSchemaConn->fetchCol("SELECT TABLE_NAME FROM `TABLES` WHERE TABLE_NAME LIKE '%users%'");
		$sql = "";
		$condition = array();
		foreach($user_tables as $table_name)
		{
			$sql .= "select * from " . $table_name . " where facebook_id = ? union ";
			$condition[] = $inFBID;
		}
		$sql = substr($sql, 0 , -6);
		$result = $inDbConn->fetchRow($sql,$condition);
		return $result;
	} 
	
	public static function getAllUserTables($inSchemaConn)
	{
		$user_tables = $inSchemaConn->fetchCol("SELECT TABLE_NAME FROM `TABLES` WHERE TABLE_NAME LIKE '%users%'");
		return $user_tables;
	}
	
	public static function getRegisterSetting ()
	{
		$db_static 			= Zend_Registry::get('db_static');
		$db_users 			= Zend_Registry::get('db_users');
		$db_users_schema 	= Zend_Registry::get('db_users_schema');
		
		//check uid
		$user_total = $db_users_schema->fetchOne("select MAX(AUTO_INCREMENT) from TABLES where TABLE_NAME like '%user%'");
			
		$current_setting = GameManager::getChannel($user_total);
		
			
		if(!$current_setting)
		{
			//passed
			$data = array (
					"data" => null,
					"status" => ReturnStatus::INTERNAL_SERVER_ERROR,
					"status_msg" => "Input Wrong Format [NOT FOUND AVAILABLE CHANNEL]"
			);
			parent::sendResponse($data);
		}
			
		if(!$found = $db_users_schema->fetchRow("select * from TABLES where TABLE_NAME =? " , $current_setting->usertable))
		{
			//passed
			$data = array (
					"data" => null,
					"status" => ReturnStatus::INTERNAL_SERVER_ERROR,
					"status_msg" => "Server not ready for registration"
			);
			parent::sendResponse($data);
		}
		return $current_setting;
	}
	
	public static function getChannel ($uid)
	{
		$config = Zend_Registry::get("channel_config");
		$result = null;
		for($i=1;$i<=$config->total_channel;$i++)
		{
			$read_row = $config->db->{"channel$i"};			
			if($read_row->startuid <= $uid and $read_row->enduid >= $uid )
			{
				$result = $read_row;
				break;
			}
		}
		return  $result;	
	} 
	
}
