<?php

class GraffitiPluginMenusGraffiti
{
	public function addMenuItems($menuSys)
	{
		$url = new Url();
		$url->module = 'Graffiti';
		$url->format = 'Admin';
		$url->action = 'SetTaggedModels';
		$link = $url->getLink('Model Tagging');
		$menuSys->addItemToSubmenu('primary', 'Settings', $link, 'Model Tagging', 'auto', $url);
	}
}

?>