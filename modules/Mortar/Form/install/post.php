<?php

class MortarFormInstallerPostscript
{
	protected $package = 'Form';
	protected $family = 'Mortar';
	protected $packageId;

	public function __construct()
	{
		$packageInfo = PackageInfo::loadByName($this->family, $this->package);
		$this->packageId = $packageInfo->getId();
	}

	public function run()
	{
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
	}
}

?>