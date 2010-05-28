<?php

class MortarActionInstallModule extends ActionBase
{
	static $requiredPermission = 'System';

	public $adminSettings = array( 'headerTitle' => 'Install Module' );

	protected $form;

	protected $success = false;

	protected $installablePackages;
	protected $installedPackages;

	protected function logic()
	{
		$query = Query::getQuery();
		$installPackage = $query['id'];

		$packageList = new PackageList();
		$installablePackages = $packageList->getInstallablePackages();
		$installedPackages = $packageList->getInstalledPackages();

		if(!isset($query['id']))
		{
			//make listing
			$this->installablePackages = $installablePackages;
			$this->installedPackages = $installedPackages;
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
					CacheControl::disableCache();
					$moduleInstaller = new ModuleInstaller($installPackage);
					$this->success = $moduleInstaller->integrate();
					CacheControl::disableCache(false);
					CacheControl::clearCache();
				}
			}else{
				//redirect to listing

				unset($this->form);
				$this->installablePackages = $installablePackages;
				$this->installedPackages = $installedPackages;
			}
		}
	}

	protected function getModuleListing($modules, $name, $url = null)
	{
		$table = new Table($name . '_module_listing');
		$table->addClass('index-listing');
		$table->addColumnLabel('package_name', 'Name');
		$table->addColumnLabel('package_description', 'Description');

		foreach($modules as $package)
		{
			$packageInfo = new PackageInfo($package);
			$meta = $packageInfo->getMeta();

			$table->newRow();

			if(isset($url)) {
				$linkToPackage = clone $url;
				$linkToPackage->id = $package;
				$table->addField('package_name', $linkToPackage->getLink($package));
			} else {
				$table->addField('package_name', $package);			
			}
			$table->addField('package_description', $meta['description']);
		}

		return $table->makeHtml();
	}

	public function viewAdmin($page)
	{
		$output = '';

		if(isset($this->installablePackages) && !isset($this->form))
		{
			$linkToSelf = Query::getUrl();
			unset($linkToSelf->locationId);

			$output .= '<h2>Available Packages</h2>';
			$output .= $this->getModuleListing($this->installablePackages, 'installable', $linkToSelf);
			$output .= '<h2>Installed Packages</h2>';
			$output .= $this->getModuleListing($this->installedPackages, 'installed');
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