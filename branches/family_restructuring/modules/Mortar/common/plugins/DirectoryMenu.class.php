<?php

class MortarPluginDirectoryMenu
{
	public function addModelMenuItems($menuSys, $model)
	{
		$url = new Url();
		$url->location = $model->getLocation()->getId();
		$url->format = 'admin';
		$url->action = 'SetDefaultPage';
		$link = $url->getLink('Set Default Page');
		$menuSys->addItemToSubmenu('secondary', $model->getType(), $link, 'Set Default Page', 0, $url);
	}
}

?>