<?php

class ChalkCorePluginBlogEntryMenu
{
	public function addModelMenuItems($menuSys, $model)
	{
		$url = new Url();
		$url->location = $model->getLocation()->getId();
		$url->format = 'admin';
		$url->action = 'Comment';
		$link = $url->getLink('Comment');
		$menuSys->addItemToSubmenu('secondary', $model->getType(), $link, 'Comment', 0, $url);
	}
}

?>