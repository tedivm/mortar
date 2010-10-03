<?php

class GraffitiPluginTagsMenu
{
	public function addModelMenuItems($menuSys, $model)
	{
		$resource = $model->getType();
		if(GraffitiTagger::canTagModelType($resource)) {
			$url = new Url();
			$url->location = $model->getLocation()->getId();
			$url->format = 'admin';
			$url->action = 'Tag';
			$link = $url->getLink('Tag');
			$menuSys->addItemToSubmenu('secondary', $resource, $link, 'Tag', 0, $url);
		}
	}
}

?>