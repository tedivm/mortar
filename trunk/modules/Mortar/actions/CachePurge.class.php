<?php

class MortarActionCachePurge extends ActionBase
{
	static $requiredPermission = 'System';

	protected function logic()
	{
		Cache::purge();

		$config = Config::getInstance();

		if(!isset($config['path']['temp']))
			return true;

		$clearPath = array();
		$clearPath[] = $config['path']['temp'] . 'outputCompression/';
		$clearPath[] = $config['path']['temp'] . 'twigCache/strings/';
		$clearPath[] = $config['path']['temp'] . 'loginTracker.sqlite';

		foreach($clearPath as $path)
			FileSystem::deleteRecursive($path);

		return true;
	}

	public function viewText()
	{
		return 'Cache purged at ' . gmdate('D M j G:i:s T Y');
	}
}

?>