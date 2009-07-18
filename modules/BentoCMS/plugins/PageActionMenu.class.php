<?php

class BentoCMSPluginPageActionMenu
{
	/**
	 * Enter description here...
	 *
	 * @param NavigationMenu $menu
	 * @param LocationModel $model
	 * @param string $format
	 */
	public function addToMenu($menu, $model, $format)
	{
		$url = new Url();
		$url->location = $model->getLocation()->getId();
		$url->format = $format;
		$url->action = 'History';
		$menu->addItem('history', $url, 'History');
	}
}

?>