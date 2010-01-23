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
	protected $backupTime;
	protected $errors = array();

	public function setPath($path)
	{
		if(!$path = realpath($path))
			throw new CoreError('Backup path must exist.');

		if(!is_dir($path))
			throw new CoreError('Backup path must be a directory.');

		if($path[strlen($path)-1] != '/')
			$path .= '/';

		$backupTime = gmdate('Ymd-Hi');

		if(is_dir($path . $backupTime))
			throw new CoreError('Can not save backup over existing backup ' . $path . $backupTime);

		mkdir($path . $backupTime, octdec('0755'));

		$this->backupPath = $path;
		$this->backupTime = $backupTime;

	}

	public function backup()
	{
		if(!isset($this->backupPath))
			throw new CoreError('Path required for creating backups.');

		if(!$this->moveFiles())
			return false;

		if(!$this->saveDatabase())
			return false;

		if(!$this->packageBackup())
			return false;

		return true;
	}

	public function getErrors()
	{
		if(count($this->errors) < 1)
			return false;
		return $this->errors;
	}

	protected function moveFiles()
	{
		$config = Config::getInstance();
		$perms = octdec('0755');
		foreach($this->paths as $name => $pathPiece)
		{
			try
			{
				if(isset($config['path'][$name]))
				{
					$backupPath = $this->backupPath . $this->backupTime . '/' . $pathPiece;

					if(!is_dir($backupPath))
						mkdir($backupPath, $perms, true);

					if(!FileSystem::copyRecursive($config['path'][$name], $backupPath, null, null, true))
						throw new FoundryBackupError('Unable to copy ' . $name . ' from ' . $config['path'][$name]);
				}

			}catch(Exception $e){
				$this->errors[] = 'Unable to copy ' . $name . ' at path ' . $config['path'][$name];
			}
		}

		return true;
	}

	protected function saveDatabase()
	{
		$mysqldumppath = Config::getBinaryPath('mysqldump');
		if(!$mysqldumppath)
			throw new FoundryBackupError('Unable to find path for executable "mysqldump"');

		$mysqldump = new ShellExec();

		if(!$mysqldump->setBinary($mysqldumppath))
			return false;

		$mysqldump->addOption('opt');

		$connectionSettings = DatabaseConnection::getDatabaseSettings('default');
		$mysqldump->addFlag('h', $connectionSettings['host']);
		$mysqldump->addFlag('u', $connectionSettings['username']);
		$mysqldump->addFlag('f');
		$mysqldump->addFlag('l');
		$mysqldump->addOption('password', $connectionSettings['password']);
		$mysqldump->addArgument($connectionSettings['dbname']);

		$sqlBackupPath = $this->backupPath . $this->backupTime . '/sql/';

		mkdir($sqlBackupPath, octdec('0755'));

		$backupFile = $sqlBackupPath . 'full.sql';
		$mysqldump->setOutputFile($backupFile);

		$mysqldump->run(array('optionDelimiter' => '='));
		// the only way to check the results are to review the last line of the file

		$tailPath = Config::getBinaryPath('tail');
		if(!$tailPath)
			throw new FoundryBackupError('Unable to find path for executable "tail"');

		$tail = new ShellExec();
		$tail->setBinary($tailPath);
		$tail->addFlag('1');
		$tail->addArgument($backupFile);
		$lastLine = $tail->run();

		if(strpos($lastLine, 'Dump completed on') === false)
		{
			$error = 'Database Backup Failed';

			if(strpos($lastLine, 'error:') !== false)
				$error .= ': ' . $lastLine;

			$this->errors[] = $error;
			return false;
		}

		return true;
	}

	protected function packageBackup()
	{
		$config = Config::getInstance();

		if(isset($config['backups']['compress']) && $config['backups']['compress'])
		{
			$destinationPath = $config['path']['temp'] . 'backups/'; // . gmdate('Ymd-Hi') . '.tar.gz';

			if($tarPath = Config::getBinaryPath('tar'))
			{

				$currentDir = getcwd();
				chdir($destinationPath);


				$tar = new ShellExec();
				$tar->setBinary($tarPath);
				$tar->addFlag('czvf');
				$tar->addArgument(gmdate('Ymd-Hi') . '.tar.gz');
				$tar->addArgument('./' . $this->backupTime);
				$tar->run();

				chdir($currentDir);

				if(file_exists($destinationPath))
				{
					FileSystem::deleteRecursive($this->backupPath . $this->backupTime);
					return true;
				}
			}
			return false;
		}

		return true;
	}

}

class FoundryBackupError extends CoreError {}

?>