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
	protected $packageList;
	
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
			

			$this->packageList = new PackageList();
			
			
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
		$template = $this->loadTemplate('adminInstallModuleListing');
		$packageList = $this->packageList->getPackageDetails();
		$output .= '';
		foreach($packageList as $packageInfo)
		{
			$packageHtml = new DisplayMaker();
			$packageHtml->set_display_template($template);
			/* description name link_to_install */
			
			$name = $packageInfo->getMeta('name');
			$packageHtml->addContent('name', $name);
			
			$description = $packageInfo->getMeta('description');
			$packageHtml->addContent('description', $description);
			
			$version = $packageInfo->getMeta('version');
			$packageHtml->addContent('version', $version);
			
			
			$url = $this->linkToSelf();
			$url->property('id', $packageInfo->getMeta('name'));
			$packageHtml->addContent('link_to_install', (string) $url);
			
			$output .= $packageHtml->make_display(true);
		}
		
		
		return $output;
		
		
	}
	
}

?>