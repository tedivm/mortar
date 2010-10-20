<?php

class DbDriverPgSql
{
	protected $driverName = 'pgsql';

	public function validate($options)
	{
		if(isset($options['dbname']))
			return true;
		return false;
	}

	public function getPdo($options)
	{
		$dsn = $this->driverName . ':';
		$class = isset($options['class']) ? $options['class'] : 'PDO';

		$dsn .= DbManager::getOptionsAsString(array('host', 'port', 'dbname', 'user', 'password'), ' ');

		$pdo = new $class($dsn);
		return $pdo;
	}
}

?>