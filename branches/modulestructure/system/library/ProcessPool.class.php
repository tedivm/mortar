<?php

class ProcessPool
{
	/**
	 * This is the path to the directory where the pid files can be stored. Should end in a slash.
	 *
	 * @var string
	 */
	static $path;

	/**
	 * Adds a process id to the specified pool.
	 *
	 * @param string $category
	 * @param int|null $pid Defaults to current process id
	 * @return int Returns the pid used
	 */
	static function addProcess($category, $pid = null)
	{
		if(is_null($pid))
			$pid = getmypid();

		$path = self::getPath($category, $pid);
		file_put_contents($path, '');
		return $pid;
	}

	/**
	 * Checks to see if a process is listed in the specified pool
	 *
	 * @param string $category
	 * @param int $pid
	 */
	static function checkProcess($category, $pid)
	{
		$pids = self::getProcesses($category);

		if(!$pids)
			return false;

		return in_array($pid, $pids);
	}

	/**
	 * Removes a pid from the pool. Its important to note this does not kill the pid, this class just keeps track
	 *
	 * @param string $category
	 * @param int|array $pid Takes a single int, array of ints, or clears the entire pool
	 */
	static function removeProcess($category, $pids = null)
	{
		if(!isset($pids))
			if(!($pids = self::getProcesses($category)))
				return; // only occurs when no processes exist to remove

		if(!is_array($pids))
			$pids = array($pids);

		foreach($pids as $pid)
			if($path = self::getPath($category, $pid))
			{
				// if not a pid skip
				if(strpos($path, '.pid') !== (strlen($path) - 4))
					continue;

				if(file_exists($path))
					unlink($path);
			}
		return;
	}

	/**
	 * This function prunes ids from the pool, first by optionally removing all pids which no longer have live processes
	 * and then by removing processes in order of age until the max process number is reached. Like removeProcess this
	 * function just clears them from the pool- the running process or other software should handle killing the process
	 * itself.
	 *
	 * @param string $category
	 * @param int $maxProcesses If false then only 'dead' processes are removed
	 * @param boolean $removeDefunct
	 */
	static function pruneProcesses($category, $maxProcesses = 0, $removeDefunct = true)
	{
		$pids = self::getProcesses($category, $removeDefunct); // this also removes defunct processes

		if(!$pids || $maxProcesses === false)
			return true;

		if($maxProcesses <= 0)
			return self::removeProcess($category);

		$processCount = count($pids);
		if($processCount <= $maxProcesses)
			return true;

		foreach($pids as $pid)
			$paths[] = self::getPath($category, $pid);

		usort($paths, array('ProcessPool', 'pidSort'));
		$removeCount = $processCount - $maxProcesses;

		for($i = 0; $i < $removeCount; $i++)
		{
			if(strpos($paths[$i], '.pid') !== (strlen($paths[$i]) - 4))
				continue;

			unlink($paths[$i]);
		}

		return true;
	}

	/**
	 * This function returns the process ids from a pool, optionally removing the pids for no longer running processes.
	 *
	 * @param string $category
	 * @param boolean $clearDefunct
	 * @return array|boolean Returns false if no processes are found.
	 */
	static function getProcesses($category, $clearDefunct = false)
	{
		if($clearDefunct && $defunctPids = self::getDefunctProcesses($category))
			self::removeProcess($category, $defunctPids);

		return self::getProcessList($category);
	}

	/**
	 * This function returns all process ids which do not have active processes
	 *
	 * @param string $category
	 * @return array|boolean Returns false if no processes are found.
	 */
	static function getDefunctProcesses($category)
	{
		$pids = self::getProcessList($category);

		if(!$pids)
			return false;

		$defunctPids = array();
		foreach($pids as $pid)
		{
			exec('ps ' . $pid, $output, $result);
			if(count($output) <= 1)
				$defunctPids[] = $pid;
			unset($output); // unset $output or else exec appends the results
		}
		return count($defunctPids) < 1 ? false : $defunctPids;
	}

	/**
	 * This function returns all process ids from a pool, regardless if they're running or not.
	 *
	 * @param string $category
	 * @return array|boolean Returns false if no processes are found.
	 */
	static protected function getProcessList($category)
	{
		$path = self::getPath($category);

		if(!is_dir($path))
			return false;

		$pidPaths = glob($path . '*.pid');
		$pids = array();
		foreach($pidPaths as $pidPath)
		{
			$pathPiece = substr($pidPath, strrpos($pidPath, '/') + 1);
			$pid = substr($pathPiece, 0, strpos($pathPiece, '.'));
			$pids[] = $pid;
		}

		return count($pids) < 1 ? false : $pids;
	}

	/**
	 * Returns the path to either the category or a specific pid file in that category. If needed it creates the path
	 * to the category itself.
	 *
	 * @param string $category
	 * @param int|null $pid
	 * @return string Path to pool or pid file
	 */
	static protected function getPath($category, $pid = null)
	{
		if(!isset(self::$path))
			self::$path = PATH_TMP;

		$path = self::$path . $category . '/';

		if(!is_dir($path))
			mkdir($path, 0700, true);

		if(isset($pid))
			$path .= $pid . '.pid';

		return $path;
	}

	/**
	 * This function is used by usort to sort an array of file paths by their last modification date, for use by the
	 * pruneProcesses function.
	 *
	 * @internal
	 */
	static function pidSort($a, $b)
	{
		$aTime = filemtime($a);
		$bTime = filemtime($b);
		return ($aTime < $bTime) ? -1 : 1;
	}
}

?>