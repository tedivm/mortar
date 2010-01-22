<?php

class FoundryActionBackup extends ActionSystem
{
	protected function systemAction()
	{
		$config = Config::getInstance();

		$backup = new FoundryBackup();
		$backup->setPath($config['paths']['temp'] . 'backups/');

		return $backup->backup();
	}
}

?>