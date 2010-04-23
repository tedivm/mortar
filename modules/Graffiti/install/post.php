<?php

class GraffitiInstallerPostscript
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
		Hook::registerPlugin('system', 'menus', 'admin', $this->packageId, 'MenusGraffiti');
		Hook::registerPlugin('Forms', 'HtmlConvert', 'tag', $this->packageId, 'FormInputTagToHtml');
		Hook::registerPlugin('Forms', 'checkSubmit', 'tag', $this->packageId, 'FormInputTagCheckSubmit');
		Hook::registerModelPlugin('All', 'baseForm', $this->packageId, 'ModelFormCategories');
		Hook::registerModelPlugin('All', 'baseForm', $this->packageId, 'ModelFormTags');
		Hook::registerModelPlugin('All', 'adminMenu', $this->packageId, 'TagsMenu');
		Hook::registerModelPlugin('All', 'actionLookup', $this->packageId, 'ModelTagAction');
		Hook::registerModelPlugin('All', 'toArray', $this->packageId, 'ModelCategoriesToArray');
		Hook::registerModelPlugin('All', 'toArray', $this->packageId, 'ModelTagsToArray');
	}
}

?>