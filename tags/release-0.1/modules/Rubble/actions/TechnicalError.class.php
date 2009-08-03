<?php

class RubbleActionTechnicalError extends ActionBase
{
	public $AdminSettings = array(	'linkTab' => 'Universal',
									'headerTitle' => 'Unknown Error',
									'EnginePermissionOverride' => true);

	static $requiredPermission = 'Read';

	public function logic()
	{
		if(isset($this->argument) && is_numeric($this->argument))
		{
			$this->ioHandler->setStatusCode($this->argument);
		}else{
			$this->ioHandler->setStatusCode(500);
		}
	}

	public function viewHtml()
	{
		$output = 'An unknown error has occured.';
		return $output;
	}

	public function viewAdmin()
	{
		$output = 'An unknown error has occured.';
		return $output;
	}

	public function viewJson()
	{

		return $output;
	}
}