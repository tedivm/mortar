<?php

class FoundryActionBackup extends ActionSystem
{

	protected $errorMessage = 'Backup failed.';
	protected $successMessage = 'System successfully backed up.';

	protected function systemAction()
	{
		$config = Config::getInstance();
		$backupPath = $config['path']['temp'] . 'backups/';

		if(!is_dir($backupPath))
			mkdir($backupPath, octdec('0755'));

		$backup = new FoundryBackup();
		$backup->setPath($backupPath);

		return $backup->backup();
	}
}

?>