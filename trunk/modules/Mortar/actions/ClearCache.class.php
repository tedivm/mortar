<?php

class MortarActionClearCache extends ActionBase
{
	static $requiredPermission = 'System';

	public $adminSettings = array( 'headerTitle' => 'Cache Cleared' );

	public function logic()
	{
		CacheControl::clearCache();
	}


	public function viewAdmin($page)
	{
		return 'Cache has been cleared.';
	}

	public function viewText()
	{
		return 'Cache cleared';
	}


}


?>