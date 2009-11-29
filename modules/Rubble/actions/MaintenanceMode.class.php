<?php

class RubbleActionMaintenanceMode extends RubbleActionAuthenticationError
{
	public $AdminSettings = array(	'linkTab' => 'Universal',
									'headerTitle' => 'Forbidden',
									'EnginePermissionOverride' => true);

	static $requiredPermission = 'Read';

	protected $authenticatedErrorCode = 503;
	protected $unauthenticatedErrorCode = 503;

	protected $errorMessage = 'The system is currently down for maintenance.';

	public function logic()
	{
		$config = Config::getInstance();
		if(isset($config['system']['maintenanceMessage']))
			$this->errorMessage = $config['system']['maintenanceMessage'];

		return parent::logic();
	}

}
?>