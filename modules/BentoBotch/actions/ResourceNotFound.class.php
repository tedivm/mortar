<?php

class BentoBotchActionResourceNotFound extends Action
{
	
	public $AdminSettings = array(	'linkTab' => 'Universal',
									'headerTitle' => '404 Error');	
	
	public function logic()
	{
		
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
}