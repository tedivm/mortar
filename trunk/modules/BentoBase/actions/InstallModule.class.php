<?php

class BentoBaseActionInstallModule extends PackageAction  
{
	static $requiredPermission = 'Read';
	
	public $AdminSettings = array('linkLabel' => 'Install',
									'linkTab' => 'System',
									'headerTitle' => 'Log In',
									'linkContainer' => 'Modules');
									
	protected $form;
	protected $loginSuccessful = false;
	
	protected function logic()
	{
		// display list
		
		//new form;
	}
	
	public function viewAdmin()
	{
		
	}
	
}

?>