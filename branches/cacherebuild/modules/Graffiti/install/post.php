<?php

class GraffitiInstallerPostscript extends MortarInstallerPostscript
{
	public $packageId;
	public $package = 'Graffiti';
	
	public function __construct()
	{
		$packageInfo = new PackageInfo($this->package);
		$this->packageId = $packageInfo->getId();
	}

	public function run()
	{
		Hook::registerPlugin('system', 'adminInterface', 'navigation', $this->packageId, 'AdminNav');
		Hook::registerPlugin('Forms', 'HtmlConvert', 'tag', $this->packageId, 'FormInputTagToHtml');
		Hook::registerPlugin('Forms', 'checkSubmit', 'tag', $this->packageId, 'FormInputTagCheckSubmit');
	}
}

?>