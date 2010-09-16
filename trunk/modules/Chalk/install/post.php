<?php

class ChalkInstallerPostscript
{
	protected $package = 'Chalk';
	protected $family = null;
	protected $packageId;

	public function __construct()
	{
		$packageInfo = PackageInfo::loadByName($this->family, $this->package);
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