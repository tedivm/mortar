<?php

class BentoBaseActionInstallModule extends PackageAction
{
	static $requiredPermission = 'System';

	public $AdminSettings = array('linkLabel' => 'Install',
									'linkTab' => 'System',
									'headerTitle' => 'Install Module',
									'linkContainer' => 'Modules');

	protected $form;
	protected $packageCount = array();
	protected $packageList;
	protected $success = false;

	protected function logic()
	{
		$info = InfoRegistry::getInstance();
		$installPackage = $info->Get['id'];

		if(!$installPackage)
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

			$PackageInfo = new PackageInfo($installPackage);
			$locationOptions = array();
			$rootLocation = new Location(1);
			$sites = $rootLocation->getChildren('site');
			foreach($sites as $site)
			{
				$siteName = $site->getName();
				$locationOptions[$site->getId()] = $siteName;

				$directories = $site->getChildren('directory');

				// one level only for now

				foreach($directories as $directory)
				{
					$locationOptions[$directory->getId()] = $siteName . '/' . $directory->getName();
				}

			}

			// make form
			$form = new Form('installModule');
			$this->form = $form;
			$form->changeSection('Main')->
				setLegend('Basic Information')->
				createInput('name')->
					setLabel('Module Name')->
					addRule('required');

			$input = $this->form->createInput('location')->
					setType('location')->
					setLabel('Location')->
					property('types', array('directory'));


			$formExtensionPath = $PackageInfo->getPath() . 'hooks/InstallModuleForm.Internal.php';
			$formExtentionClassname = $PackageInfo->getName() . 'InstallModuleForm';

			if(!class_exists($formExtentionClassname, false))
			{
				if(is_readable($formExtensionPath))
				{
					include $formExtensionPath;
				}
			}

			if(class_exists($formExtentionClassname, false))
			{
				$formExtention = new $formExtentionClassname();
				$moduleForm = $formExtention->getForm();
				$form->merge($moduleForm);
			}

			if($form->checkSubmit())
			{
				try{

				$inputHandler = $form->getInputhandler();
				if($formExtention)
				{
					$settings = $formExtention->getSettings($inputHandler);
				}

				$installer = new InstallModule($installPackage, $inputHandler['name'], $inputHandler['location'], $settings);
				if($installer->installModule())
				{
					$this->success = true;
				}

				}catch(Exception $e){
					$this->AdminSettings['headerSubTitle'] = 'An error occured';
				}
			}else{

			}
		}
		Cache::clear('packages', 'adminTabs');
	}

	public function viewAdmin()
	{
		if(isset($this->packageList))
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
				$url->property('engine', 'Admin');
				$packageHtml->addContent('link_to_install', (string) $url);

				$output .= $packageHtml->make_display(true);
			}

		}elseif($this->form && !$this->success){

			$output .= $this->form->makeDisplay();


		}elseif($this->success){
			$output = 'Module successfully installed.';
		}

		return $output;
	}

}

?>