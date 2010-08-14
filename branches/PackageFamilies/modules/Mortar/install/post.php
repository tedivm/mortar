<?php

class MortarInstallerPostscript
{
	protected $package = 'Mortar';
	protected $packageId;

	public function __construct()
	{
		$packageInfo = new PackageInfo($this->package);
		$this->packageId = $packageInfo->getId();
	}

	public function run()
	{
		ControlRegistry::registerControl('admin', $this->packageId, 'Add');
		ControlRegistry::registerControl('admin', $this->packageId, 'Index');
		ControlRegistry::registerControl('admin', $this->packageId, 'RecentLocations');

		Hook::registerPlugin('system', 'menus', 'admin', $this->packageId, 'MenusAdminBase');
		Hook::registerPlugin('model', 'All', 'adminMenu', $this->packageId, 'MenusAdminModels');

		Hook::registerPlugin('Forms', 'HtmlConvert', 'template', $this->packageId, 'FormInputTemplateToHtml');

		Hook::registerPlugin('Forms', 'HtmlConvert', 'location', $this->packageId, 'FormInputLocationToHtml');
		Hook::registerPlugin('Forms', 'checkSubmit', 'location', $this->packageId, 'FormInputLocationCheckSubmit');

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

		Hook::registerPlugin('Forms', 'checkSubmit', 'richtext',
								$this->packageId, 'FormInputRichtextCheckSubmit');

		CronManager::registerJob('CachePurge', $this->packageId, 'module', 30);
	}
}

?>