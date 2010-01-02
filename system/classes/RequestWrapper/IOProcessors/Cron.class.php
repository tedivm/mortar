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
			throw new CronError('Unable to retrieve process id for cron handler.');

		ActiveUser::changeUserByName('Cron');
		$pidPath = $this->getPidPath();
		$pidDir = dirname($pidPath);

		// delete and remake cron folder so the only pid is this one

		if(is_dir($pidDir))
			deltree($pidDir);

		mkdir($pidDir, 0700);

		if(!touch($pidPath))
			throw new CronError('Unable to set pid file for the cron handler.');
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
		// In case the previous action changed it we reset the active user.
		ActiveUser::changeUserByName('Cron');

		$query = Query::getQuery();

		// If the cleanup job was the last to run we bail out.
		if($query['action'] == 'CronEnd')
			return false;

		// Whatever job has the current process's pid for its jobPid value is the job that just finished,
		// so we clear out the pid and reset the lastRun time.
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

		// If another process erased our pid file or there are no more jobs (setNextJob returns false) we set the
		// cleanup action as the next, and final, action.
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
		// This epic beast below checks to see if any jobs match the criteria to run. It discounts any items that have
		// restrictions preventing them from running at this time, as well as those which haven't had enough of a delay
		// between runs (as set by the job) and those already running (possibly in another process) and then priorizes
		// the rest based on the last time they ran.
		$db = DatabaseConnection::getConnection('default_read_only');
		$results = $db->query("SELECT
							id,
							moduleId,
							locationId,
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
								( (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(lastRun)) >= (minutesBetweenRequests * 60) + 15 )
							)

						AND
							(moduleId IS NOT NULL OR locationId IS NOT NULL)

						ORDER BY lastRun ASC
						LIMIT 1");

		if($results->num_rows)
		{
			$actionRow = $results->fetch_array();

			if(in_array($actionRow['id'], self::$actionsPerformed))
				return false;

			self::$actionsPerformed[] = $actionRow['id'];

			$stmt = DatabaseConnection::getStatement('default');
			$stmt->prepare('UPDATE cronJobs
							SET jobPid = ?
							WHERE
								id = ?
								AND jobPid = 1');

			if(!$stmt->bindAndExecute('ii', self::$pid, $actionRow['id']))
					return false;

			$newQuery = array();
			$newQuery['format'] = 'Text';
			$newQuery['action'] = $actionRow['actionName'];
			$newQuery['lastRun'] = $actionRow['lastRun'];

			if(isset($actionRow['moduleId']) && is_numeric($actionRow['moduleId']))
			{
				$module = new PackageInfo($actionRow['moduleId']);
				$newQuery['module'] = $module->getName();
			}elseif(isset($actionRow['locationId']) && is_numeric($actionRow['locationId'])){
				$type = 'location';
				$newQuery['location'] = $actionRow['locationId'];
			}else{
				return false;
			}

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

class CronError extends CoreError {}
?>