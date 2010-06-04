<?php

class GraffitiPluginMenusGraffiti
{
	public function addMenuItems($menuSys)
	{
		$menuSys->addItemToSubmenu(	'primary', 
						'Tags and Categories',
						'<a href="#">Tags and Categories</a>', 
						'Tags and Categories',
						'auto');

		$url = new Url();
		$url->module = 'Graffiti';
		$url->format = 'Admin';
		$url->action = 'SetTaggedModels';
		$link = $url->getLink('Model Settings');
		$menuSys->addItemToSubmenu('primary', 'Tags and Categories', $link, 'Model Settings', 'auto', $url);

		$url = new Url();
		$url->type = 'Category';
		$url->action = 'Index';
		$url->format = 'Admin';
		$link = $url->getLink("Categories");
		$menuSys->addItemToSubmenu('primary', 'Tags and Categories', $link, 'Categories', 'auto', $url);

		$url = clone $url;
		$url->action = 'Add';
		$link = $url->getLink("Add New Category");
		$menuSys->addItemToSubmenu('primary', 'Tags and Categories', $link, 'Add New Category', 'auto', $url);

	}
}

?>