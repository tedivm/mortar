<?php

class LithoCoreInstallerPostscript
{
	protected $package = 'Core';
	protected $family = 'Litho';
	protected $packageId;

	public function __construct()
	{
		$packageInfo = PackageInfo::loadByName($this->family, $this->package);
		$this->packageId = $packageInfo->getId();
	}

	public function run()
	{
		Hook::registerModelPlugin('Directory', 'getAllowedChildrenTypes', $this->packageId, 'AllowPageType', true);
		Hook::registerModelPlugin('Site', 'getAllowedChildrenTypes', $this->packageId, 'AllowPageType', true);
		Hook::registerModelPlugin('Page', 'adminMenu', $this->packageId, 'PageMenu', true);
	}
}

?>