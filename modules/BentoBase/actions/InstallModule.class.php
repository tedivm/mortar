<?php

class BentoBaseActionInstallModule extends PackageAction  
{
	static $requiredPermission = 'Read';
	
	public $AdminSettings = array('linkLabel' => 'Install',
									'linkTab' => 'System',
									'headerTitle' => 'Install Module',
									'linkContainer' => 'Modules');
									
	protected $form;
	protected $packageCount = array();
	
	protected function logic()
	{
		$info = InfoRegistry::getInstance();
		
		if(!$package)
		{
			
			$db = dbConnect('default_read_only');
			$packageCountRecord = $db->query('SELECT mod_package, COUNT(*) as numInstalls FROM `modules` GROUP BY mod_package');
		
			while($packageRow = $packageCountRecord->fetch_assoc())
			{
				$packageCount[$packageRow['mod_package']] = $packageRow['numInstalls'];
			}
			
			$this->packageCount = $packageCount;
			
			'SELECT mod_package, COUNT(*) FROM `modules` GROUP BY mod_package';
			$packages = new PackageList();
			
			
			
			
			//list packages
		}else{
			
			// make form
			$form = new Form('installModule');
			
			if($form->checkSubmit())
			{
				
			}else{
				
			}
		}
		
		// display list
		
		//new form;
	}
	
	public function viewAdmin()
	{
		
	}
	
}

?>