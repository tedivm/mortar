<?php

class BentoCMSActionListPages extends PackageAction
{
	static $requiredPermission = 'Read';
	
	public $AdminSettings = array('linkLabel' => 'List Page',
									'linkTab' => 'Content',
									'headerTitle' => 'List Pages',
									'linkContainer' => 'CMS');	
	protected $pages;
	
	public function logic()
	{
		$packageList = new PackageInfo('BentoCMS');
		
		$modules = $packageList->getModules('Read');
		$db = dbConnect('default_read_only');
		
		//var_dump($modules);
		
		$pages = array();
		foreach($modules as $module)
		{
			$result = $db->query('SELECT page_id, page_name');
			
			
		}
		
		
		//$moduleList->getPackageDetails();
		
		
	}
	
	public function viewAdmin()
	{
		
	}
	
	
	
	
	
}




?>