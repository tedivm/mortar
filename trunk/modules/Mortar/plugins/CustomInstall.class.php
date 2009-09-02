<?php

class MortarPluginCustomInstall
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
		Hook::registerPlugin('Forms', 'HtmlConvert', 'location', $this->packageId, 'FormInputLocationToHtml');
		Hook::registerPlugin('Forms', 'HtmlConvert', 'user', $this->packageId, 'FormInputUserToHtml');
		Hook::registerPlugin('Forms', 'checkSubmit', 'user', $this->packageId, 'FormInputUserCheckSubmit');

		CronManager::registerJob('CachePurge', 'Mortar', 'module', 30);
	}
}

?>