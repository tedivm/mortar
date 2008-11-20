<?php

class BentoBaseActionLogOut extends PackageAction  
{
	static $requiredPermission = 'Read';
	
	public $AdminSettings = array('linkLabel' => 'Log Out',
									'linkTab' => 'Universal',
									'headerTitle' => 'Log Out');
									
	public function logic()
	{
		$info = InfoRegistry::getInstance();
		$info->User->loadUserByName('guest');
	}
	
	
	public function viewAdmin()
	{
		return 'You have been logged out.';
	}
	
	
	public function viewHtml()
	{
		return 'You have been logged out.';
	}
									
}


?>