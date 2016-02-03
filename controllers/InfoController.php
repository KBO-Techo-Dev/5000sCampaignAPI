<?php
require 'BaseController.php';

class InfoController extends BaseController
{
	public function init()
	{
		$this->view->title = 'Info';
		$this->view->page = 'info';
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
		if( isset($request_data['data']['language'])
			&& isset($request_data['data']['command'])	)
		{
			try
			{
				$result = $this->getInfo( $request_data['data']['command'] );
				if( isset($result[0]) )
				{
					$this->view->dict = Utils::getDictSetByLanguage( $request_data['data']['language'], 'index', $request_data['data']['command']);
					$info = $this->extractInfo( $result, $request_data['data']['command'], $request_data['data']['token']);
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
	private function getInfo($inCommand)
	{
		$db_static = Zend_Registry::get('db_static');
		
		$result = array();
		
		if( $inCommand == 'news' )
			$result = $db_static->fetchAll('SELECT * FROM db_news WHERE enable=? ORDER BY display_order ASC, date DESC LIMIT 10', array('yes'));
		elseif( $inCommand == 'articles' )
			$result = $db_static->fetchAll('SELECT * FROM db_articles WHERE enable=? ORDER BY display_order ASC, date DESC LIMIT 10', array('yes'));
		elseif( $inCommand == 'campaign' )
			$result = $db_static->fetchAll('SELECT * FROM db_campaign WHERE enable=? ORDER BY display_order ASC, date DESC LIMIT 10', array('yes'));
			
		return $result;
	}
	private function extractInfo($inResult, $inCommand, $inToken)
	{
		$db_share = Zend_Registry::get('db_share');
		
		$info = array();
		
		if( $inCommand == 'news' )
		{
			foreach( $inResult as $row )
			{
				array_push($info, array(
					'news_id' 		=> $row['news_id'],
					'author_id'		=> 0,
					'author_name'	=> '',
					'date'			=> $row['date'],
					'title'			=> $this->view->dict[$row['codename'] . '.TITLE'],
					'brief'			=> $this->view->dict[$row['codename'] . '.BRIEF'],
					'detail'		=> $this->view->dict[$row['codename'] . '.DETAIL'],
				));
			}				
		}
		elseif( $inCommand == 'articles' )
		{
			foreach( $inResult as $row )
			{
				array_push($info, array(
					'articles_id' 	=> $row['articles_id'],
					'author_id'		=> 0,
					'author_name'	=> '',
					'date'			=> $row['date'],
					'title'			=> $this->view->dict[$row['codename'] . '.TITLE'],
					'brief'			=> $this->view->dict[$row['codename'] . '.BRIEF'],
					'detail'		=> $this->view->dict[$row['codename'] . '.DETAIL'],
				));
			}			
		}
		elseif( $inCommand == 'campaign' )
		{
			$uid = GameManager::getUidByToken($inToken);
			
			foreach( $inResult as $row )
			{
				$recent_signatures = array();
				if( $result_signatures = $db_share->fetchAll('SELECT * FROM signatures WHERE enable=? AND campaign_id=? ORDER BY date DESC, time DESC LIMIT 30', array('yes', $row['campaign_id'])) )
				{
					foreach( $result_signatures as $c )
					{
						array_push($recent_signatures, array(
							'campaign_id'		=> $c['campaign_id'],
							'uid'				=> $c['uid'],
							'username'			=> $c['username'],
							'first_name'		=> $c['first_name'],
							'last_name'			=> $c['last_name'],
							'date'				=> $c['date'],
							'time'				=> $c['time'],
						));
					}						
				}
				$comments = array();						
				if( $result_comment = $db_share->fetchAll('SELECT * FROM comments WHERE enable=? AND campaign_id=? ORDER BY date DESC, time DESC LIMIT 30', array('yes', $row['campaign_id'])) )
				{
					foreach( $result_comment as $c )
					{
						array_push($comments, array(
							'campaign_id'		=> $c['campaign_id'],
							'uid'				=> $c['uid'],
							'username'			=> $c['username'],
							'display_name'		=> $c['display_name'],
							'display_comment'	=> $c['display_comment'],
							'date'				=> $c['date'],
							'time'				=> $c['time'],
						));
					}						
				}					
				$timeline = array();						
				if( $result_timeline = $db_share->fetchAll('SELECT * FROM timeline WHERE enable=? AND campaign_id=? ORDER BY date DESC, time DESC, id DESC LIMIT 30', array('yes', $row['campaign_id'])) )
				{
					foreach( $result_timeline as $c )
					{
						array_push($timeline, array(
							'campaign_id'		=> $c['campaign_id'],
							'uid'				=> $c['uid'],
							'username'			=> $c['username'],
							'display_name'		=> $c['display_name'],
							'display_content'	=> $this->view->dict[$c['display_content'] . '.DETAIL'],
							'date'				=> $c['date'],
							'time'				=> $c['time'],
							
							'icon'				=> $c['icon'],
							'icon_color'		=> $c['icon_color'],
							'image'				=> $c['image'],
							'image_url'			=> $c['image_url'],
						));
					}						
				}
				$letters = array();
				$disparage_name = explode('|', $row['disparage_name']);
				$disparage_letter = explode('|', $row['disparage_letter']);
				foreach( $disparage_letter as $j => $l )
				{
					if( $l != 'none' )
					{
						array_push($letters, array(
							'name'		=> $disparage_name[$j],
							'content'	=> $this->view->dict[$l],
						));							
					}
				}

				$already_support = false;
				if( $uid )
				{
					$users = new Users();
					$me = $users->init($uid);
					$already_support = ($record = $me->_db_channel->fetchRow('SELECT id FROM signatures WHERE campaign_id=? AND uid=? LIMIT 1', array($row['campaign_id'], $uid))) ? true : false;		
				}
				
				array_push($info, array(
					'campaign_id' 		=> $row['campaign_id'],
					'author_id'			=> 0,
					'author_name'		=> '',
					'date'				=> $row['date'],
					'title'				=> $this->view->dict[$row['codename'] . '.TITLE'],
					'brief'				=> $this->view->dict[$row['codename'] . '.BRIEF'],
					'detail'			=> $this->view->dict[$row['codename'] . '.DETAIL'],
					'copied_head'		=> $this->view->dict[$row['codename'] . '.COPIED_HEAD'],
					'copied_full'		=> $this->view->dict[$row['codename'] . '.COPIED_FULL'],

					'target'			=> $row['target'],
					'signatures'		=> $row['signatures'],
					
					'recent_signatures'	=> $recent_signatures,
					'comments'			=> $comments,
					'letters'			=> $letters,
					'timeline'			=> $timeline,
					
					'already_support'	=> $already_support,
				));
			}
		}
		return $info;
	}
}