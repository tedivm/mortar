<?php

class ChalkPluginBlogEntryActionMenu
{
	public function addToMenu($menu, $model, $format)
	{
		$url = new Url();
		$url->location = $model->getLocation()->getId();
		$url->format = $format;
		$url->action = 'Comment';
		$menu->addItem('comment', $url, 'Comment');
	}
}

?>