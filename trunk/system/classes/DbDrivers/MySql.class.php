<?php

class DbDriverPgSql
{
	protected $driverName = 'mysql';

	public function validate($options)
	{
		if((isset($options['host']) || isset($options['unix_socket'])) && isset($options['dbname']))
			return true;

		return false;
	}

	public function getPdo($options)
	{
		$dsn = $this->driverName . ':';
		$class = isset($options['class']) ? $options['class'] : 'PDO';

		$dsn .= DbManager::getOptionsAsString(array('host', 'port', 'dbname', 'unix_socket'), ';');

		if(!isset($options['username']))
			$options['username'] = null;

		if(!isset($options['password']))
			$options['password'] = null;

		$pdo = new $class($dsn, $options['username'], $options['password']);
		return $pdo;

	}
}

?>