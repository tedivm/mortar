<?php

class HtmlEngine extends Engine 
{
	public $engine_type = 'Html';
		

	public $default_action = 'MainDisplay';
	public $page;
	
	protected function StartEngine()
	{

		$config = Config::getInstance();
		$get = Get::getInstance();
		
		$this->page = ActivePage::get_instance();
		$user = ActiveUser::get_instance();		
		
	}
	
	
	public function display()
	{
		$page = ActivePage::get_instance();
		$page->addRegion('PathToPackage', $this->main_action->info['PathToPackage']);
		
		return $this->page->makeDisplay();
	}
	
	
	
	protected function processAction($actionResults)
	{
		if(isset($actionResults) && strlen($actionResults) > 0)
			$this->page['main_content'] = $actionResults;
	}
	
	
}


?>