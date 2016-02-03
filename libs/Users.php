<?php
class Users
{
	public $_data = '';	
	public $_config_channel = '';
	public $_db_users = '';
	public $_db_users_schema = '';	
	public $_db_channel = '';
	public $_db_channel_schema = '';
	
	public function __construct() { }
	
	public function init($inUid)
	{
		$db_static 				= Zend_Registry::get('db_static');
		$db_users 				= Zend_Registry::get('db_users');
		$db_users_schema		= Zend_Registry::get('db_users_schema');		
		$this->_db_users 		= $db_users;		
		$this->_db_users_schema = $db_users_schema;	
		
		//Find user data
		$user_data = GameManager::getUserDataByUid($inUid,$db_users,$db_users_schema);
		$this->_data = $user_data;
		if(!$this->_data) throw new Exception("Not Found User Data",Users::NOT_FOUND_USER_STATUS);		
		return $this->commonInitiate();
	}
	
	public function login($inUsername , $inPassword)
	{
		$db_static = Zend_Registry::get('db_static');		
		$db_users = Zend_Registry::get('db_users');
		$db_users_schema = Zend_Registry::get('db_users_schema');		
		$this->_db_users = $db_users;		
		$this->_db_users_schema = $db_users_schema;

		//Find user data
		$user_data = GameManager::getUserDataByUsrPwd($inUsername,$inPassword,$db_users,$db_users_schema);
		$this->_data = $user_data;
		if(!$this->_data) throw new Exception("Not Found User Data",Users::NOT_FOUND_USER_STATUS);
		return  $this->commonInitiate();		
	}
	
	private function commonInitiate()
	{
		$this->_config_channel = GameManager::getChannel($this->_data["uid"]);
		
		$config_temp = new Zend_Config(
				array(
						'database' => array(
								'adapter' 	=> $this->_config_channel->adapter,
								'params' 	=> $this->_config_channel->params
						)
				)
		);
		$db_channel = GameManager::getDbConnection($config_temp,$this->_config_channel->dbname);
		$this->_db_channel = $db_channel;
		
		$config_temp = new Zend_Config(
				array(
						'database' => array(
								'adapter' 	=> 'pdo_mysql',
								'params' 	=> array(
										'host'		=> $this->_config_channel->params->host,
										'dbname' 	=> "information_schema",
										'username' 	=> $this->_config_channel->params->username,
										'password' 	=> $this->_config_channel->params->password,
								)
						)
				)
		);
		$db_channel_schema = GameManager::getDbConnection($config_temp,$this->_config_channel->dbname.".information_schema");
		$this->_db_channel_schema = $db_channel_schema;		
		
		return $this;
	}
	
	public function updateUser($inData)
	{
		if(isset($this->_data["uid"]))
		{
			$result = $this->_db_users->update($this->_config_channel->usertable , $inData , array("uid=?" => $this->_data["uid"]));
			foreach ($inData as $key => $value )
			{
				$this->_user[$key] = $value;
			}
		}
	}
	
	
}