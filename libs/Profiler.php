<?php 

class Profiler
{
	public function __construct() {}
	/**
	 * Makes directory, returns TRUE if exists or made
	 *
	 * @param string $pathname The directory path.
	 * @return boolean returns TRUE if exists or made or FALSE on failure.
	 */
	public static function mkdirRecursive($inPath, $inMode)
	{
		is_dir(dirname($inPath)) || Utils::mkdirRecursive(dirname($inPath), $inMode);
		umask(0);
		return is_dir($inPath) || @mkdir($inPath, $inMode);
	}
	public function dump( $inPage, $inAction , $inShowDetails = FALSE)
	{	
		$me = Zend_Registry::isRegistered('me')? Zend_Registry::get('me') : null;
		$config = Zend_Registry::get('config');
		if($me and $me->_data)
		{
			if($me->_data['uid'] != $config->profiler_uid)
			{
				exit();
			}
		}
		else {
			exit();
		}
		$db_share = Zend_Registry::get("db_share");
		$db_static =  Zend_Registry::get("db_static");
		$db_crm = Zend_Registry::get("db_crm");
		$db_users = Zend_Registry::get("db_users");
		$data_bases = array(
			'share' =>  $db_share ,
			'static' =>  $db_static ,
			'crm' => $db_crm ,
			'users' => $me->_db_users ,
			'users_schema' => $me->_db_users_schema ,
			'channel' =>  $me->_db_channel ,
			'channel_schema' => $me->_db_channel_schema ,
			'general_users' => $db_users
		);
		// Write information
		foreach($data_bases as $key => $inDb)
		{
			if ($profiler = $inDb->getProfiler()) {
				//echo ($profiler->getEnabled() ? 'yes' : 'no');
				if ($profiler->getQueryProfiles()) {
					try {
						// Logger initialization
						$date = date('Ymd');
						$hour = date('H');
						$dir = $config->profiler_root . APP_FOLDER;
						$dir .= "profiler/$inPage/$inAction/$key/";
						$this->mkdirRecursive($dir, 0777);
						$filename = $me->_data['uid'] . '_';
						$filename .= (($inShowDetails) ? $inPage . '_' . date('YmdHis') : $inPage);
						//echo $dir . $filename;
						$writer = new Zend_Log_Writer_Stream($dir . $filename . '.txt');
						$formatter = new Zend_Log_Formatter_Simple("%timestamp%: %message%" . PHP_EOL);
						$writer->setFormatter($formatter);
						$logger = new Zend_Log($writer);
	
						$totalTime = $profiler->getTotalElapsedSecs();
						$queryCount = $profiler->getTotalNumQueries();
						$longestTime = 0;
						$longestQuery = null;
	
						foreach ($profiler->getQueryProfiles() as $query) {
							if ($query->getElapsedSecs() > $longestTime) {
								$longestTime  = $query->getElapsedSecs();
								$longestQuery = $query->getQuery();
							}
							if ($inShowDetails) {
								$logger->info('(' . $query->getElapsedSecs() . ') ' . $query->getQuery());
							}	
						}
						if ($inShowDetails) {
							$logger->info('-----------------------------------------------------------------');
						}
						$logger->info('URL: ' . $_SERVER['REQUEST_URI']);				
						$logger->info('Executed ' . $queryCount . ' queries in ' . $totalTime . ' seconds');
						$logger->info('Average query length: ' . $totalTime / $queryCount . ' seconds');
						$logger->info('Queries per second: ' . $queryCount / $totalTime);
						$logger->info('Longest query length: ' . $longestTime);
						$logger->info('Longest query: ' . $longestQuery);
						$logger->info('-----------------------------------------------------------------');
						$logger = null;
					} catch (Exception $e) {
						
					}
				}
			}
			
		}
	}
}