<?php

class RubbleActionAuthenticationError extends ActionBase
{
	public $AdminSettings = array(	'linkTab' => 'Universal',
									'headerTitle' => 'Forbidden',
									'EnginePermissionOverride' => true);

	static $requiredPermission = 'Read';

	public function logic()
	{
		if(isset($this->argument) && is_numeric($this->argument))
		{
			$this->ioHandler->setStatusCode($this->argument);
		}else{
			if(ActiveUser::isLoggedIn())
			{
				$this->ioHandler->setStatusCode(403);
			}else{
				$this->ioHandler->setStatusCode(401);
			}
		}
	}

	public function viewHtml()
	{
		$output = 'You do not have the appropriate permissions to access this resource.';
		return $output;
	}

	public function viewAdmin()
	{
		$output = 'You do not have the appropriate permissions to access this resource.';
		return $output;
	}

	public function viewJson()
	{

		return $output;
	}
}