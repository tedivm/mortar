<?php

class TesseraActionForumRead extends ModelActionLocationBasedIndex
{
        public $adminSettings = array('headerTitle' => 'Read', 'listType' => 'template', 'paginate' => true);
        public $htmlSettings = array('headerTitle' => 'Read', 'listType' => 'template', 'paginate' => true);

	protected $listOptions = array('browseBy' => 'creationDate', 'order' => 'DESC');

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
