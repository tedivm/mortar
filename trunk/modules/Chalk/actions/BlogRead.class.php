<?php

class ChalkActionBlogRead extends ModelActionLocationBasedIndex
{
	public function viewAdmin($page)
	{
		if(isset($this->model['title']))
			$page->addRegion('title', htmlentities($this->model['title']));

		$menu = $page->getMenu('actions', 'modelNav');
		$this->makeModelActionMenu($menu, $this->model, 'Admin');
		return parent::viewHtml($page);
	}
}

?>