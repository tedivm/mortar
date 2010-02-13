<?php

class LithoInstallerPostscript
{
	protected $package = 'Litho';
	protected $packageId;

	public function __construct()
	{
		$packageInfo = new PackageInfo($this->package);
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