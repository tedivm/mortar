<?php

class MortarActionCronEnd extends ActionBase
{
	static $requiredPermission = 'System';

	protected function logic()
	{

	}

	public function viewText()
	{
		return 'Cron engine finished at ' . gmdate('D M j G:i:s T Y');
	}
}

?>