<?php

class GraffitiPluginMenusGraffiti extends MortarPluginAdminNav
{
	public function addMenuItems($menuSys)
	{
		$url = new Url();
		$url->module = 'Graffiti';
		$url->format = 'Admin';
		$url->action = 'SetTaggedModels';
		$link = $url->getLink('Model Tagging');
		$this->addItemToSubment('primary', 'Settings', $link, 'Model Tagging', 'auto', $url);
	}
}

?>