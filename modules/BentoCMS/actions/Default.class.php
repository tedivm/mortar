<?php

class BentoCMSActionDefault extends Action 
{
	protected $pageName;
	
	public function logic()
	{
		$info = InfoRegistry::getInstance();
		$this->pageName = $info->Get['id'];
	}
	
	public function viewHtml()
	{
		$info = InfoRegistry::getInstance();
		$activePage = ActivePage::get_instance();
		if(!$activePage->loadCmsPage($this->$pageName, $this->moduleId))
			throw new ResourceNotFoundError();
	}
}

?>