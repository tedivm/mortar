<?php

class MortarActionCachePurge extends ActionBase
{
	static $requiredPermission = 'System';

	protected function logic()
	{
		Cache::purge();
	}

	public function viewText()
	{
		return 'Cache purged at ' . gmdate('D M j G:i:s T Y');
	}
}

?>