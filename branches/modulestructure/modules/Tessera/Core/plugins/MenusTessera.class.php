<?php

class TesseraCorePluginMenusTessera
{
	public function addMenuItems($menuSys)
	{
		$url = new Url();
		$url->module = PackageInfo::loadByName('Tessera', 'Core');
		$url->format = 'Admin';
		$url->action = 'CommentSettings';
		$link = $url->getLink('Comment Settings');
		$menuSys->addItemToSubmenu('primary', 'Settings', $link, 'Comment Settings', 'auto', $url);
	}
}

?>