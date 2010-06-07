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
		Hook::registerPlugin('system', 'menus', 'admin', $this->packageId, 'MenusTessera');

		Hook::registerModelPlugin('Directory', 'getAllowedChildrenTypes', $this->packageId, 'AllowForumType', true);
		Hook::registerModelPlugin('Site', 'getAllowedChildrenTypes', $this->packageId, 'AllowForumType', true);
		Hook::registerModelPlugin('All', 'getAllowedChildrenTypes', $this->packageId, 'AllowDiscussionType', true);
		Hook::registerModelPlugin('All', 'firstSaveLocation', $this->packageId, 'AddDiscussionChild', true);
		Hook::registerModelPlugin('All', 'extraContent', $this->packageId, 'ModelDisplayComments', true);
		Hook::registerModelPlugin('All', 'toArray', $this->packageId, 'ModelCommentsToArray', true);
	}
}
?>