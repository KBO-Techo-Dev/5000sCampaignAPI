<?php
require 'BaseController.php';

class SignatureController extends BaseController
{
	public function init()
	{
		$this->view->title = 'Signature';
		$this->view->page = 'signature';
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
	public function signAction()
	{
		$request_data = $this->view->request_data;
		if( isset($request_data['data']['campaign_id'])
			&& $request_data['data']['campaign_id'] > 0
			&& isset($request_data['data']['uid'])
			&& isset($request_data['data']['comment'])
			&& isset($request_data['data']['fb_share'])
			&& isset($request_data['data']['show_name']) )
		{
			$db_static = Zend_Registry::get('db_static');
			$db_share = Zend_Registry::get('db_share');
			
			$me = Zend_Registry::get('me');
			
			// ALEADY SIGN?
			if( $row = $me->_db_channel->fetchRow('SELECT id FROM signatures WHERE campaign_id=? AND uid=? LIMIT 1', array($request_data['data']['campaign_id'], $request_data['data']['uid'])) )
			{
				$data = array (
					"data" => null,
					"status" => ReturnStatus::ALREADY_SIGN_TO_SUPPORT,
					"status_msg" => "Already Sign to support campaign"
				);
				parent::sendResponse($data);			
			}
			else
			{
				$campaign_codename = $db_static->fetchOne('SELECT codename FROM db_campaign WHERE campaign_id=? LIMIT 1', array($request_data['data']['campaign_id']));
				try {
					$insert_full = array(
						'campaign_id'		=> $request_data['data']['campaign_id'],
						'campaign_codename'	=> $campaign_codename,
						'uid'				=> $me->_data['uid'],
						'username'			=> $me->_data['username'],
						'first_name'		=> $me->_data['first_name'],
						'last_name'			=> $me->_data['last_name'],
						'display_name'		=> $request_data['data']['show_name'] == 'yes' ? $me->_data['first_name'] . ' ' . $me->_data['last_name'] : '',
						'display_comment'	=> $request_data['data']['comment'],
						'date'				=> date('Y-m-d'),
						'time'				=> date('H:i:s'),
						'country_code'		=> $me->_data['country'],
						'fb_share'			=> $request_data['data']['fb_share'],
						'show_name'			=> $request_data['data']['show_name'],
					);					
					$me->_db_channel->insert('signatures', $insert_full);
					// ++signatures
					$db_static->query('UPDATE db_campaign SET signatures=signatures+1 WHERE campaign_id=?', array($request_data['data']['campaign_id']));
					// Send email
					$this->view->dict = Utils::getDictSet($me, 'index', 'campaign');
					$this->sendEmail(
						$me->_data['username'],
						Utils::resolveTag($this->view->dict['THANKS_FOR_SUPPORT.SUBJECT'], array(
							'[TITLE]' => $this->view->dict[$campaign_codename . '.TITLE'],
						)),
						Utils::resolveTag($this->view->dict['THANKS_FOR_SUPPORT.CONTENT'], array(
							'[NAME]'	=> $me->_data['first_name'],
							'[IMAGE]'	=> '<img src="http://www.5000s.org/views/images/campaign/' . $request_data['data']['campaign_id'] . '/1.jpg" width="200">',
							'[BRIEF]'	=> $this->view->dict[$campaign_codename . '.BRIEF'],
							'[FB_SHARE_BUTTON]' => '<img src="http://www.5000s.org/views/images/fb-share-button.png" width="210">',
						))
					);
				} catch (Exception $e) {
					$data = array (
						"data" 			=> null,
						"status" 		=> ReturnStatus::FAILED_SIGN_TO_SUPPORT,
						"status_msg" 	=> $e->getMessage(),
					);
					parent::sendResponse($data);							
				}
				if( $request_data['data']['show_name'] == 'yes' )
				{
					$insert_signatures = array(
						'campaign_id'		=> $request_data['data']['campaign_id'],
						'campaign_codename'	=> $campaign_codename,
						'uid'				=> $me->_data['uid'],
						'username'			=> $me->_data['username'],
						'first_name'		=> $me->_data['first_name'],
						'last_name'			=> $me->_data['last_name'],
						'date'				=> date('Y-m-d'),
						'time'				=> date('H:i:s'),
					);
					try { $db_share->insert('signatures', $insert_signatures); } catch (Exception $e) {}
				}
				if( $request_data['data']['comment'] )
				{
					$insert_comment = array(
						'campaign_id'		=> $request_data['data']['campaign_id'],
						'campaign_codename'	=> $campaign_codename,
						'uid'				=> $me->_data['uid'],
						'username'			=> $me->_data['username'],
						'first_name'		=> $me->_data['first_name'],
						'last_name'			=> $me->_data['last_name'],
						'display_name'		=> $me->_data['first_name'] . ' ' . $me->_data['last_name'],
						'display_comment'	=> $request_data['data']['comment'],
						'date'				=> date('Y-m-d'),
						'time'				=> date('H:i:s'),
					);
					try { $db_share->insert('comments', $insert_comment); } catch (Exception $e) {}
					$last_insert_id = $db_share->lastInsertId();
					if( $request_data['data']['show_name'] == 'no' )
					{
						$db_share->update('comments', array('display_name' => 'Anonymous'), 'id=' . $last_insert_id);
					}
				}
				$data = array (
					"data" 			=> null,
					"status" 		=> 200,
					"status_msg" 	=> 'Success'
				);
				parent::sendResponse($data);				
			}
		}
		else
		{
			$data = array (
				"data" => null,
				"status" => 305,
				"status_msg" => "Input Wrong Format[?]"
			);
			parent::sendResponse($data);			
		}
	}
}