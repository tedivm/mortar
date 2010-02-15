<?php

class MortarActionCachePurge extends ActionBase
{
	static $requiredPermission = 'System';

	protected function logic()
	{
		CacheControl::purge();

		$config = Config::getInstance();

		if(!isset($config['path']['temp']))
			return true;

		MortarLoginTracker::purge();

		$clearPath = array();
		$clearPath[] = $config['path']['temp'] . 'outputCompression/';
		$clearPath[] = $config['path']['temp'] . 'twigCache/strings/';

		foreach($clearPath as $path)
		{
			if(is_dir($path))
				FileSystem::deleteRecursive($path);
		}

		return true;
	}

	public function viewText()
	{
		return 'Cache purged at ' . gmdate('D M j G:i:s T Y');
	}
}

?>