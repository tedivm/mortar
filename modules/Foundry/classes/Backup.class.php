<?php

class FoundryBackup
{
	protected $paths = array(
			'theme' => 'data/themes',
			'icons' => 'data/icons',
			'fonts' => 'data/fonts',
			'config' => 'data/configuration',
			'modules' => 'modules',
			'javascript' => 'javascript',
			'mainclasses' => 'system/classes',
			'abstracts' => 'system/abstracts',
			'engines' => 'system/engines',
			'library' => 'system/library',
			'functions' => 'system/functions',
			'interfaces' => 'system/interfaces',
			'templates' => 'system/templates',
			'views' => 'system/views',
			'thirdparty' => 'system/thirdparty' );

	protected $backupPath;

	public function setPath($path)
	{
		if(!$path = realpath($path))
			throw new CoreError('Backup path must exist.');

		if(!is_dir($path))
			throw new CoreError('Backup path must be a directory.');

		if($path[strlen($path)-1] != '/')
			$path .= '/';

		$path = $path . gmdate('Ymd-Hi');

		if(is_dir($path))
			throw new CoreError('Can not backup over existing backing ' . $path);

		mkdir($path, octdec('0755'));

		$this->backupPath = $path . '/';
	}

	public function backup()
	{
		if(!isset($this->backupPath))
			throw new CoreError('Path required for creating backups.');

		$this->moveFiles();
	}

	protected function moveFiles()
	{
		$config = Config::getInstance();
		$perms = octdec('0755');

		foreach($this->paths as $name => $pathPiece)
		{
			if(isset($config['path'][$name]))
			{
				$backupPath = $this->backupPath . $pathPiece;

				if(!is_dir($backupPath))
					mkdir($backupPath, $perms, true);

				FileSystem::copyRecursive($config['path'][$name], $backupPath, null, null, true);
			}
		}
	}

	protected function saveDatabase()
	{
		$mysqldumppath = self::getPathToMysqlDump();

		$mysqldump = new ShellExec();

		if(!$mysqldump->setBinary($mysqldumppath))
			return false;

		$mysqldump->addOption('opt');

		$connectionSettings = DatabaseConnection::getDatabaseSettings('default');
		$mysqldump->addFlag('h', $connectionSettings['host']);
		$mysqldump->addFlag('u', $connectionSettings['username']);
		$mysqldump->addFlag('p', $connectionSettings['password']);
		$mysqldump->addArgument($connectionSettings['dbname']);

		$backupFile = $this->backupPath . 'full.' . gmdate("Y-m-d-H:i:s") . '.sql';
		$mysqldump->setOutputFile($backupFile);

		$results = $mysqldump->run();

		// the only way to check the results are to review the last line of the file


		$tail = new ShellExec();
		$tail->setBinary('tail');
		$tail->addFlag('n', 1);
		$tail->addOption($backupFile);
		$lastLine = $tail->run();

		if(strpos($lastLine, 'error:') === false)
			return true;

		// handle error
	}

	static function getPathToMysqlDump()
	{
		$config = Config::getInstance();

		if(isset($config['binaries']['mysqldump']))
	   {
			$path = $config['binaries']['mysqldump'];
	   }else{
			$path = shell_exec('which mysqldump');
			$path = trim($path);
		}

		if(strlen($path) < 9)
			throw new CoreError('Unable to find mysqldump binary.');

	   $shellStart = shell_exec('/opt/local/bin/mysqldump5 --version');

	   if(strpos($shellStart, 'mysqldump') !== 0)
			throw new CoreError('Supplied invalid path for mysqldump binary.');

		return $path;
	}



}



?>