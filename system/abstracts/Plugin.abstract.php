<?php

abstract class Plugin extends ModuleBase 
{
	public $option;
	
	static $scope;
	
	public function __construct($id, $option)
	{
		$this->moduleId = $id;
		$this->option = $option;
		$this->loadSettings();
		
		if(method_exists($this, 'logic'))
		{
			$this->logic();
		}
		
	}
	
	
	
}


?>