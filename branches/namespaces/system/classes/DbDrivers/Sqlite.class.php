<?php

class DbDriverSqlite
{
	protected $driverName = 'sqlite';

	public function validate($options)
	{
		if(isset($options['path']) || isset($options['memory']))
			return true;

		return false;
	}

	public function getPdo($options)
	{
		$dsn = $this->driverName . ':';
		$class = isset($options['class']) ? $options['class'] : 'PDO';

		if(isset($options['path']))
		{
			$dsn .= $options['path'];
		}elseif($options['memory']){
			$dsn .= ':memory:';
		}else{
			throw new PdoError('Unable to load sqlite database due to missing settings');
		}

		$pdo = new $class($dsn);
		return $pdo;
	}
}

?>