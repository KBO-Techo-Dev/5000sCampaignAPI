<?php
require 'BaseController.php';

class StatController extends BaseController
{
	public function init()
	{
		$this->view->title = 'Stat';
		$this->view->page = 'stat';
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
	public function queryAction()
	{
		$request_data = $this->view->request_data;
		if( isset($request_data['data']['mode'])
			&& isset($request_data['data']['date'])	)
		{
			try
			{
				$result = $this->getInfo( $request_data['data']['mode'], $request_data['data']['date'] );
				if( isset($result[0]) )
				{
					$info = $this->extractInfo( $result );
					$data = array (
						"data" => $info,
						"status" => 200,
						"status_msg" => "Success"
					);
					parent::sendResponse($data);
				}
				else {
					$data = array (
						"data" => null,
						"status" => ReturnStatus::DATA_NOT_FOUND,
						"status_msg" => "Data not found."
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
				"status_msg" => "Input Wrong Format[Language]"
			);
			parent::sendResponse($data);
		}
	}
	private function getInfo($inMode, $inDate)
	{
		$db_users = Zend_Registry::get('db_users');
		
		$result = array();
		
		if( $inMode == 1 )
			$result = $db_users->fetchAll('SELECT nationality AS caption,COUNT(uid) AS nums FROM users_001 WHERE date=? GROUP BY nationality ORDER BY nums DESC', array($inDate));
		elseif( $inMode == 2 )
			$result = $db_users->fetchAll('SELECT (2015 - year_of_birth) AS caption,COUNT(uid) AS nums FROM users_001 WHERE date=? GROUP BY (2015 - year_of_birth) ORDER BY nums DESC', array($inDate));
			
		return $result;
	}
	private function extractInfo($inResult)
	{
		$info = array();
		
		foreach( $inResult as $row )
		{
			array_push($info, array(
				'caption'	=> $row['caption'],
				'nums'		=> $row['nums'],
			));
		}
		return $info;
	}
}