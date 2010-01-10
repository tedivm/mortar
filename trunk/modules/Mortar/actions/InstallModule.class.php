<?php

class MortarActionInstallModule extends ActionBase
{
	static $requiredPermission = 'System';

	public $adminSettings = array( 'headerTitle' => 'Install Module' );

	protected $form;

	protected $success = false;

	protected $installablePackages;

	protected function logic()
	{
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
					Cache::$runtimeDisable = true;
					$moduleInstaller = new ModuleInstaller($installPackage);
					$this->success = $moduleInstaller->fullInstall();
					Cache::$runtimeDisable = false;
					Cache::clear();
				}
			}else{
				//redirect to listing

				unset($this->form);
				$this->installablePackages = $installablePackages;
			}
		}
	}

	public function viewAdmin($page)
	{
		$this->setTitle($this->adminSettings['headerTitle']);

		$output = '';
		if(isset($this->installablePackages) && !isset($this->form))
		{
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
				$query = Query::getQuery();

				if(isset($query['id'])) {
					$packageInfo = new PackageInfo($query['id']);
					$models = $packageInfo->getModels();

					if(count($models) > 0) {
						$url = Query::getUrl();
						$url->id = $packageInfo->getId();
						$url->action = 'ModulePermissions';
						$this->ioHandler->addHeader('Location', (string) $url);
						return true;
					}
				}
				
				$output = 'Module successfully installed';
			}else{
				$output = $this->form->getFormAs('Html');
			}

			$output = ($this->success) ? 'Module successfully installed' : $this->form->getFormAs('Html');
		}

		return $output;
	}

}

?>