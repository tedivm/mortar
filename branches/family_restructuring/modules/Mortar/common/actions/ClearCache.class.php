<?php

class MortarActionClearCache extends ActionBase
{
	static $requiredPermission = 'System';

	public static $settings = array( 'Base' => array( 'headerTitle' => 'Cache Cleared' ) );

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