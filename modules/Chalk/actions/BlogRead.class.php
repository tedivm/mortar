<?php

class ChalkActionBlogRead extends ModelActionLocationBasedIndex
{
        public $adminSettings = array('headerTitle' => 'Read', 'listType' => 'template', 'paginate' => false);
        public $htmlSettings = array('headerTitle' => 'Read', 'listType' => 'template', 'paginate' => false);

	protected $listOptions = array('browseBy' => 'publishDate', 'order' => 'ASC');

	public function viewAdmin($page)
	{
		$page->setTitle($this->model->getDesignation());

		return $this->getAdminDetails($page) . parent::viewAdmin($page);
	}

	public function viewHtml($page)
	{
		$page->setTitle($this->model->getDesignation());

		return parent::viewHtml($page);	
	}

}

?>