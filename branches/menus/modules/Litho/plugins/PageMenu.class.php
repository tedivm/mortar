<?php

class LithoPluginPageMenu
{
	public function addModelMenuItems($menuSys, $model)
	{
		$url = new Url();
		$url->location = $model->getLocation()->getId();
		$url->format = 'admin';
		$url->action = 'History';
		$link = $url->getLink('History');
		$menuSys->addItemToSubmenu('secondary', 'Page', $link, 'History', 0);
	}
}

?>