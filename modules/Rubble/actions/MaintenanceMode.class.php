<?php

class RubbleActionMaintenanceMode extends RubbleActionAuthenticationError
{
	public $adminSettings = array(	'headerTitle' => 'Maintenance',
					'EnginePermissionOverride' => true);
	public $htmlSettings = array(	'headerTitle' => 'Maintenance');

	static $requiredPermission = 'Read';

	protected $authenticatedErrorCode = 503;
	protected $unauthenticatedErrorCode = 503;

	protected $errorMessage = 'The system is currently down for maintenance.';

	public function logic()
	{
		$config = Config::getInstance();
		if(isset($config['system']['maintenanceMessage']) && strlen($config['system']['maintenanceMessage']))
			$this->errorMessage = $config['system']['maintenanceMessage'];

		return parent::logic();
	}

	public function viewHtml($page)
	{
		return $this->errorMessage;
	}

}
?>