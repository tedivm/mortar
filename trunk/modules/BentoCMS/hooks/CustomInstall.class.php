<?php

class BentoCMSPluginCustomInstall
{
	protected $package;
	protected $packageId;

	public function __construct($package)
	{
		$this->package = $package;
		$packageInfo = new PackageInfo($package);
		$this->packageId = $packageInfo->getId();
	}

	public function run()
	{
	//	Hook::registerModelPlugin('Directory', 'getAllowedChildrenTypes', $this->packageId, 'allowPageType', true);
	//	Hook::registerModelPlugin('Site', 'getAllowedChildrenTypes', $this->packageId, 'allowPageType', true);
	}
}

?>