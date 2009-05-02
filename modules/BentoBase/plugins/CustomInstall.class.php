<?php

class BentoBasePluginCustomInstall
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
		Hook::registerPlugin('system', 'adminInterface', 'navigation', $this->packageId, 'AdminNav');
	}
}

?>