<?php

class RubbleActionTechnicalError extends RubbleActionAuthenticationError
{
	public $adminSettings = array(	'headerTitle' => 'Unknown Error',
					'EnginePermissionOverride' => true);
	public $htmlSettings = array(	'headerTitle' => 'Unknown Error');

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
		$page->setTitle($this->htmlSettings['headerTitle']);
		$output = 'An unknown error has occured.';
		return $output;
	}

	public function viewAdmin($page)
	{
		$page->setTitle($this->adminSettings['headerTitle']);
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