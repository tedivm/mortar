<?php

class BentoBotchActionTechnicalError extends Action
{
	public $AdminSettings = array(	'linkTab' => 'Universal',
									'headerTitle' => 'Unknown Error');

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