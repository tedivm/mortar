<?php

class TesseraInstallerPostscript
{
	protected $package = 'Tessera';
	protected $packageId;

	public function __construct()
	{
		$packageInfo = new PackageInfo($this->package);
		$this->packageId = $packageInfo->getId();
	}

	public function run()
	{
		Hook::registerModelPlugin('Directory', 'getAllowedChildrenTypes', $this->packageId, 'AllowForumType', true);
		Hook::registerModelPlugin('Site', 'getAllowedChildrenTypes', $this->packageId, 'AllowForumType', true);
		Hook::registerModelPlugin('BlogEntry', 'getAllowedChildrenTypes', $this->packageId, 'AllowDiscussionType', true);
		Hook::registerModelPlugin('BlogEntry', 'firstSaveLocation', $this->packageId, 'AddDiscussionChild', true);
	}
}
?>