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
		Hook::registerPlugin('system', 'menus', 'admin', $this->packageId, 'MenusAdminBase');
		Hook::registerPlugin('Forms', 'HtmlConvert', 'location', $this->packageId, 'FormInputLocationToHtml');

		Hook::registerPlugin('Forms', 'HtmlConvert', 'user', $this->packageId, 'FormInputUserToHtml');
		Hook::registerPlugin('Forms', 'checkSubmit', 'user', $this->packageId, 'FormInputUserCheckSubmit');

		Hook::registerPlugin('Forms', 'HtmlConvert', 'membergroup',
								$this->packageId, 'FormInputMembergroupToHtml');
		Hook::registerPlugin('Forms', 'checkSubmit', 'membergroup',
								$this->packageId, 'FormInputMembergroupCheckSubmit');

		Hook::registerPlugin('Forms', 'HtmlConvert', 'datetime', 
								$this->packageId, 'FormInputDatetimeToHtml');
		Hook::registerPlugin('Forms', 'checkSubmit', 'datetime', 
								$this->packageId, 'FormInputDatetimeCheckSubmit');
		Hook::registerPlugin('Forms', 'Metadata', 'Base', $this->packageId, 'FormInputDatetimeMetadata');

		Hook::registerPlugin('Forms', 'HtmlConvert', 'title', $this->packageId, 'FormInputTitleToHtml');

		CronManager::registerJob('CachePurge', 'Mortar', 'module', 30);
	}
}

?>