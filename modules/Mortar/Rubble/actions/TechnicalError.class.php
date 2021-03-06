<?php

class MortarRubbleActionTechnicalError extends MortarRubbleActionAuthenticationError
{
	public static $settings = array( 'Base' => 
		array('headerTitle' => 'Unknown Error', 'EnginePermissionOverride' => true) );

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

	public function viewHtml($page)
	{
		$output = 'An unknown error has occured.';
		return $output;
	}

	public function viewAdmin($page)
	{
		$output = 'An unknown error has occured.';
		return $output;
	}

	public function viewJson()
	{

		return $output;
	}

	public function viewText()
	{
		return 'An unknown error occured.';
	}

}

?>