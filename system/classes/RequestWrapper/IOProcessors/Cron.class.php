<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage RequestWrapper
 */

/**
 * This class processes actions via cron
 *
 * @package System
 * @subpackage RequestWrapper
 */
class IOProcessorCron extends IOProcessorCli
{
	static $pid;

	static $actionsPerformed = array();

	protected $actionOutputs = array();

	protected function start()
	{
		self::$pid = getmypid();
		if(!is_numeric(self::$pid))
			throw new CoreError('Unable to retrieve process id for cron handler.');

		ActiveUser::changeUserByName('System');
		$pidPath = $this->getPidPath();
		$pidDir = dirname($pidPath);

		// delete and remake cron folder so the only pid is this one

		if(is_dir($pidDir))
			deltree($pidDir);

		mkdir($pidDir, 0700);

		if(!touch($pidPath))
			throw new CoreError('Unable to set pid file for the cron handler.');
	}

	/**
	 * This function sets the programming environment to match that of the system and method calling it
	 *
	 * @access protected
	 */
	protected function setEnvironment()
	{
		$module = new PackageInfo('Mortar');

		$newQuery = array();
		$newQuery['format'] = 'Text';
		$newQuery['action'] = 'CronStart';
		$newQuery['module'] = 'Mortar'; //$module->getId();

		Query::setQuery($newQuery);
	}

	protected function getPidPath()
	{
		$config = Config::getInstance();
		$cronPath = $config['path']['temp'] . 'cron/';

		$pidPath = $cronPath . self::$pid;
		return $pidPath;
	}

	public function nextRequest()
	{
		$query = Query::getQuery();

		if($query['action'] == 'CronEnd')
			return false;

		if($query['action'] != 'CronStart')
		{
			$stmt = DatabaseConnection::getStatement('default');
			$stmt->prepare('UPDATE cronJobs
								SET jobPid = 1,
									lastRun = NOW()
								WHERE
									jobPid = ?');
			$stmt->bindAndExecute('i', self::$pid);
		}

		if(!file_exists($this->getPidPath()) || !$this->setNextJob())
		{
			$newQuery = array();
			$newQuery['format'] = 'Text';
			$newQuery['action'] = 'CronEnd';
			$newQuery['module'] = 'Mortar';

			Query::setQuery($newQuery);
		}
		return true;
	}

	protected function setNextJob()
	{
		$db = DatabaseConnection::getConnection('default_read_only');
		$results = $db->query("SELECT
							module,
							actionName,
							lastRun
					FROM cronJobs
					WHERE

						# make sure this is runnable
						 	jobPid = 1

						AND # If time restrictions are in place then we should stick to them

							if (restrictTimeStart > restrictTimeEnd,
								if(restrictTimeStart < CURTIME(),
									CURTIME() > restrictTimeEnd
								,
									CURTIME() < restrictTimeEnd
								)
							,
								( restrictTimeStart IS NULL OR restrictTimeStart <= CURTIME() )
								AND	( restrictTimeEnd IS NULL OR restrictTimeEnd > CURTIME() )
							)

						AND	#Check to see if its restricted by certain days of the week
							( restrictTimeDayOfWeek IS NULL
								OR restrictTimeDayOfWeek LIKE
									CONCAT('%', DATE_FORMAT(NOW(), '%w'), '%' ))

						AND #Check to see if its restricted by certain days of the month
							( restrictTimeDayOfMonth IS NULL
								OR restrictTimeDayOfMonth LIKE
									CONCAT('%', DATE_FORMAT(NOW(), '%d'), '%' ))

						AND	# Check to see if the required amount of time has elapsed
							(lastRun IS NULL
								OR
								( (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(lastRun)) >= (minutesBetweenRequests * 60) + 60 )
							)

						ORDER BY lastRun ASC
						LIMIT 1");

		if($results->num_rows)
		{
			$actionRow = $results->fetch_array();
			$actionRow['module'];
			$actionRow['actionName'];

			if(isset(self::$actionsPerformed[$actionRow['module']][$actionRow['actionName']]))
				return false;

			self::$actionsPerformed[$actionRow['module']][$actionRow['actionName']] = true;

			$stmt = DatabaseConnection::getStatement('default');
			$stmt->prepare('UPDATE cronJobs
								SET jobPid = ?
								WHERE
									module = ?
									AND  actionName LIKE ?');
			if(!$stmt->bindAndExecute('iis', self::$pid, $actionRow['module'], $actionRow['actionName']))
					return false;

			$newQuery = array(
						'format' => 'Text',
						'action' => $actionRow['actionName'],
						'lastRun' => $actionRow['lastRun']);

			$module = new PackageInfo($actionRow['module']);
			$newQuery['module'] = $module->getName();

			Query::setQuery($newQuery);
			return true;
		}else{
			return false;
		}
	}


	public function output($output)
	{
		if(strlen($output) < 2)
			return;
		echo $output . PHP_EOL;
		$this->actionOutputs[] = $output;
	}

	public function close()
	{
		$pidPath = $this->getPidPath();
		if(file_exists($pidPath))
			unlink($pidPath);

		if(count($this->actionOutputs) <= 2)
			return;

		foreach($this->actionOutputs as $actionOutput)
		{

		}
	}

}

?>