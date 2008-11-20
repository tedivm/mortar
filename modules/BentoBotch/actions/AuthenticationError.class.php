<?php

class BentoBotchActionAuthenticationError extends Action
{
	public $AdminSettings = array(	'linkTab' => 'Universal',
									'headerTitle' => 'Forbidden');		
	
	public function logic()
	{
		
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