<?php

class ChalkInstallerPostscript
{
	protected $package = 'Chalk';
	protected $packageId;

	public function __construct()
	{
		$packageInfo = new PackageInfo($this->package);
		$this->packageId = $packageInfo->getId();
	}

	public function run()
	{
		Hook::registerModelPlugin('Directory', 'getAllowedChildrenTypes', $this->packageId, 'AllowBlogType', true);
		Hook::registerModelPlugin('Site', 'getAllowedChildrenTypes', $this->packageId, 'AllowBlogType', true);
		Hook::registerModelPlugin('BlogEntry', 'adminMenu', $this->packageId, 'BlogEntryMenu', true);
	}
}

?>