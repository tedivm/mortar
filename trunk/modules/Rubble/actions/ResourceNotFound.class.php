<?php

class RubbleActionResourceNotFound extends RubbleActionAuthenticationError
{

	public $AdminSettings = array(	'linkTab' => 'Universal',
									'headerTitle' => '404 Error');
	static $requiredPermission = 'Read';

	public function logic()
	{
		if(isset($this->argument) && is_numeric($this->argument))
		{
			$this->ioHandler->setStatusCode($this->argument);
		}else{
			$this->ioHandler->setStatusCode(404);
		}
	}

	public function viewHtml()
	{
		$output = 'The page or resource you are looking for cannot be found.';
		return $output;
	}

	public function viewAdmin()
	{

		$output = 'The page or resource you are looking for cannot be found.';
		return $output;
	}

	public function viewJson()
	{

		return $output;
	}


	public function viewText()
	{
		return 'THe resource you are looking for can not be found.';
	}
}