<?php

class ChalkActionBlogRead extends ModelActionLocationBasedIndex
{
        public $adminSettings = array();

	public function viewAdmin($page)
	{
		if(isset($this->model['title']))
			$page->setTitle($this->model['title']);
		elseif(isset($this->model['name']))
			$page->setTitle($this->model['name']);

		return parent::viewHtml($page);
	}
	
	protected function getModelListingClass()
	{
		$locationListing = parent::getModelListingClass();
		$locationListing->setOption('browseBy', 'publishDate');
		$locationListing->setOption('order', 'DESC');
		return $locationListing;
	}
}

?>