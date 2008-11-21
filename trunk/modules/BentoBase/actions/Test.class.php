<?php

class BentoBaseActionTest extends PackageAction  
{
	//static $requiredPermission = 'Read';
	
	public $AdminSettings = array('linkLabel' => 'Test',
									'linkTab' => 'Main',
									'headerTitle' => 'Test',
									'linkContainer' => 'Test');
	
	protected function logic()
	{
		
	}
	
	public function viewHtml()
	{
		$config = Config::getInstance();
		
		if($config['id'] == 'admin')
		{
			$adminUrl = new Url();
			$adminUrl->engine = 'Admin';
			
			//return $adminUrl;
			
			header('Location: ' . $adminUrl);
		}		
		
		$page = ActivePage::get_instance();
		$page['title'] = 'Test Title';
		return 'This is the main page';
	}
	
	public function viewAdmin()
	{
		$packageList = new PackageList();
		
		//$list = $packageList->getPackages();

		return 'This is the main page';
	}
	
	
}



?>