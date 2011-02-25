<?php

class MortarCoreActionCronStart extends ActionBase
{
	static $requiredPermission = 'System';


	protected function logic()
	{
		$db = DatabaseConnection::getConnection('default_read_only');

		$results = $db->query('SELECT jobPid
						FROM cronJobs
						WHERE jobPid > 50');

		if($results->num_rows)
			while($row = $results->fetch_array())
		{
			if($this->checkPid($row['jobPid']))
				continue;

			$stmt = DatabaseConnection::getStatement('default');
			$stmt->prepare('UPDATE cronJobs
								SET jobPid = 1
								WHERE
									jobPid = ?');

			$stmt->bindAndExecute('i', $row['jobPid']);
		}
	}


	protected function checkPid($pid)
	{
		if(!is_numeric($pid))
			return false;

		exec('ps ' . $pid, $output, $result);

		return count($output) > 1;
	}



	public function viewText()
	{
		return 'Cron engine activated at ' . gmdate('D M j G:i:s T Y');
	}


}

?>