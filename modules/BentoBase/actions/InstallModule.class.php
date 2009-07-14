<?php

class BentoBaseActionInstallModule extends ActionBase
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
		$query = Query::getQuery();
		$installPackage = $query['id'];

		$packageList = new PackageList();
		$installablePackages = $packageList->getInstallablePackages();

		if(!isset($query['id']))
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
				//redirect to listing
				unset($this->form);
				$this->installablePackages = $installablePackages;
			}

		}

		Cache::$runtimeDisable = false;
		Cache::clear();
	}

	public function viewAdmin($page)
	{
		$output = '';
		if(isset($this->installablePackages) && !isset($this->form))
		{
			$theme = $page->getTheme();
			$template = $theme->getTemplateFromPackage('adminInstallModuleListing', $this->package);

			$linkToSelf = Query::getUrl();
			unset($linkToSelf->locationId);

			foreach($this->installablePackages as $package)
			{
				$linkToPackage = clone $linkToSelf;
				$linkToPackage->id = $package;

				$output .= $linkToPackage->getLink($package) . '<br>';

			//	$packageInfo = new PackageInfo($package);
			}
		}elseif($this->form){
			if($this->success)
			{
				$output = 'Module successfully installed';
			}else{
				$output = $this->form->makeHtml();
			}

			$output = ($this->success) ? 'Module successfully installed' : $this->form->makeHtml();
		}

		return $output;
	}

}

?>