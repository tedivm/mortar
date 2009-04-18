<?php

class BentoBaseActionClearCache extends ActionBase
{
	static $requiredPermission = 'System';

	public $AdminSettings = array('linkLabel' => 'Clear',
									'linkTab' => 'System',
									'headerTitle' => 'Cache Cleared',
									'linkContainer' => 'Cache');

	public function logic()
	{
		Cache::clear();
	}


	public function viewAdmin()
	{
		return 'Cache has been cleared.';
	}


}


?>