<?php

class MortarActionInstallModule extends ActionBase
{
	static $requiredPermission = 'System';

	public static $settings = array( 'Base' => array( 'headerTitle' => 'Install Module' ) );

	protected $form;

	protected $success = false;

	protected $installablePackages;
	protected $installedPackages;


	protected $installPackage;
	protected $installFamily;

	protected function logic()
	{
		$query = Query::getQuery();

		$packageList = new PackageList();
		$installablePackages = $packageList->getInstallablePackages();
		$installedPackages = $packageList->getInstalledPackages();

		$this->installablePackages = $installablePackages;
		$this->installedPackages = $installedPackages;

		if(isset($query['id']))
		{

			if(strpos($query['id'], '-') === false)
			{
				$installFamily = 'orphan';
				$installPackage = $query['id'];
			}else{
				$tmp = explode('-', $query['id']);
				$installFamily = $tmp[0];
				$installPackage = $tmp[1];
			}

			if(isset($installablePackages[$installFamily])
			   && in_array($installPackage, $installablePackages[$installFamily]) )
			{

				$packageInfo = PackageInfo::loadByName($installFamily, $installPackage);
				$packageName = $packageInfo->getFullName();


				$this->form = new Form($this->actionName . '_' . $packageName);
				$this->form->createInput('confirm')->
					setType('submit')->
					property('value', 'Install ' . $packageName);

				if($this->form->checkSubmit())
				{
					CacheControl::disableCache();
					$moduleInstaller = new ModuleInstaller($packageInfo);
					$this->success = $moduleInstaller->integrate();
					CacheControl::disableCache(false);
					CacheControl::clearCache();
				}
			}
		}
	}

	protected function getModuleListing($family, $modules, $name, $url, $install = false)
	{
		$table = new Table($name . '_module_listing');
		$table->addClass('index-listing');
		$table->addColumnLabel('package_name', 'Name');
		$table->addColumnLabel('package_description', 'Description');
		$table->addColumnLabel('package_actions', 'Actions');

		foreach($modules as $package)
		{
			$packageInfo = PackageInfo::loadByName($family, $package);
			$meta = $packageInfo->getMeta();

			$table->newRow();
			$table->addField('package_name', $package);
			$table->addField('package_description', $meta['description']);

			$linkToPackage = clone $url;
			if($install) {
				$action = 'Install';
			} else {
				$package = $packageInfo->getId();
				$linkToPackage->action = 'ModulePermissions';
				$action = 'Permissions';
			}
			$linkToPackage->id = $package;

			$table->addField('package_actions', $linkToPackage->getLink($action));
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
			foreach($this->installablePackages as $family => $modules)
			{
				$familyLabel = $family != 'orphan' ? $family : 'Standalone';
				$output .= '<h3>' . $familyLabel . '</h3>';
				$output .= $this->getModuleListing($family, $modules, $familyLabel . '_installable', $linkToSelf, true);
			}

			$output .= '<h2>Installed Packages</h2>';
			foreach($this->installedPackages as $family => $modules)
			{
				$familyLabel = $family != 'orphan' ? $family : 'Standalone';
				$output .= '<h3>' . $familyLabel . '</h3>';
				$output .= $this->getModuleListing($family, $modules, $familyLabel . '_installed', $linkToSelf, false);
			}


		}elseif($this->form){
			if($this->success)
			{
				if(isset($this->installPackage) && isset($this->installFamily))
				{
					$packageInfo = PackageInfo::loadByName($this->installFamily, $this->installPackage);
					$models = $packageInfo->getModels();

					if(count($models) > 0)
					{
						$url = Query::getUrl();
						$url->id = $packageInfo->getId();
						$url->action = 'ModulePermissions';
						$url->first = 'yes';
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