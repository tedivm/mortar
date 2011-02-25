<?php

class MortarCoreInstallerPostscript
{
	protected $package = 'Core';
	protected $family = 'Mortar';
	protected $packageId;

	public function __construct()
	{
		$packageInfo = PackageInfo::loadByName($this->family, $this->package);
		$this->packageId = $packageInfo->getId();
	}

	public function run()
	{
		ControlRegistry::registerControl('admin', $this->packageId, 'Add');
		ControlRegistry::registerControl('admin', $this->packageId, 'Index');
		ControlRegistry::registerControl('admin', $this->packageId, 'RecentLocations');

		Hook::registerPlugin('system', 'menus', 'admin', $this->packageId, 'MenusAdminBase');
		Hook::registerPlugin('model', 'All', 'adminMenu', $this->packageId, 'MenusAdminModels');

                Hook::registerModelPlugin('Directory', 'adminMenu', $this->packageId, 'DirectoryMenu', true);
                Hook::registerModelPlugin('Site', 'adminMenu', $this->packageId, 'DirectoryMenu', true);

		CronManager::registerJob('CachePurge', $this->packageId, 'module', 30);
	}
}

?>