<?php

class BentoBotchActionTechnicalError extends ActionBase
{
	public $AdminSettings = array(	'linkTab' => 'Universal',
									'headerTitle' => 'Unknown Error',
									'EnginePermissionOverride' => true);

	public function logic()
	{

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