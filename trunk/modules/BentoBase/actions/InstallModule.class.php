<?php

class BentoBaseActionInstallModule extends PackageAction
{
	static $requiredPermission = 'System';

	public $AdminSettings = array('linkLabel' => 'Install',
									'linkTab' => 'System',
									'headerTitle' => 'Install Module',
									'linkContainer' => 'Modules');

	protected $form;

	protected $success = false;

	protected $installablePackages;

	protected function logic()
	{
		Cache::$runtimeDisable = true;

		$packageList = new PackageList();
		$info = InfoRegistry::getInstance();
		$installPackage = $info->Get['id'];
		$installablePackages = $packageList->getInstallablePackages();

		if(!$installPackage)
		{
			//make listing
			$this->installablePackages = $installablePackages;
		}else{

			if(in_array($installPackage, $installablePackages))
			{
				$packageInfo = new PackageInfo($installPackage);
				$this->form = new Form($this->actionName . '_' . $installPackage);
				$this->form->createInput('confirm')->
					setType('submit')->
					property('value', 'Install ' . $installPackage);

				if($this->form->checkSubmit())
				{
					$moduleInstaller = new ModuleInstaller($installPackage);
					$this->success = $moduleInstaller->fullInstall();
				}

			}else{
				unset($this->form);
				$this->installablePackages = $installablePackages;
				//redirect to listing
			}

		}

		Cache::clear('packages');
	}

	public function viewAdmin()
	{
		if(isset($this->installablePackages) && !isset($this->form))
		{
			$template = $this->loadTemplate('adminInstallModuleListing');

			foreach($this->installablePackages as $package)
			{
				$packageInfo = new PackageInfo($package);

				$packageDisplay = new DisplayMaker();
				$packageDisplay->setDisplayTemplate($template);
				$packageDisplay->addContent('name', $package);

				$packageDisplay->addContent('description', $packageInfo->getMeta('description'));

				$installLink = $this->linkToSelf();
				$installLink->property('id', $package);
				$installLink->property('engine', 'Admin');
				$packageDisplay->addContent('link_to_install', $installLink);
				$packageDisplay->addContent('version', $this->packageInfo->getMeta('version'));
				// link_to_install

				$output .= $packageDisplay->makeDisplay();
			}
		}elseif($this->form){
			if($this->success)
			{
				$output = 'Module successfully installed';
			}else{
				$output = $this->form->makeDisplay();
			}
		}

		return $output;
	}

}

?>