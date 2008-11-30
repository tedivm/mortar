<?php

class AdminEngine extends Engine 
{
	public $engine_type = 'Admin';
		
	public $default_action = 'MainDisplay';
	
	protected $requiredPermission = 'Admin';

	protected $returnMode;
	
	
	protected function startEngine()
	{
		$get = Get::getInstance();
		$config = Config::getInstance();
		
		if($get['modulePackage'] && !$config['module'])
		{
			$config['modulePackage'] = ereg_replace("[^A-Za-z0-9]", "", $get['modulePackage']);
			$this->className = ($config['modulePackage'] . 'Action' . $config['action']);
			$this->pathToModule = $config['path']['modules'] . $config['modulePackage'] . '/';
			$this->runMethod = 'viewGeneric' . $this->engine_type;
		}
		
		$this->returnMode = (isset($get['ajax'])) ? 'ajax' : 'full'; //full or ajax
		
		
		$page = ActivePage::get_instance();
		$page->addRegion('title', 'BentoBase Admin');
		
		if(isset($get['ajax']))
		{
			
			$page->setTemplate('
			{# script #}
			
			$(#content).html(\'{# main_content #}\');');
			
		}else{
			
			$page->setTemplate('index.html', 'admin');
		}
		
		
	}	
	

	
	public function display()
	{
		$page = ActivePage::get_instance();
		$page->addRegion('PathToPackage', $this->main_action->info['PathToPackage']);
		$info = InfoRegistry::getInstance();
		if($this->returnMode == 'full')
		{
			if(class_exists('AdminNavigation', false) || include($info->Configuration['path']['mainclasses'] . 'AdminNavigation.class.php'))
			{
				$adminNav = new AdminNavigation();
				$tab = ($this->main_action->AdminSettings['linkTab']) ? $this->main_action->AdminSettings['linkTab'] : 'Main';
				
				$adminLinks = $adminNav->getLinks($tab);
				$adminTabs = $adminNav->getTabs($tab);
			
				$page->addRegion('navbar', $adminLinks);
				$page->addRegion('navtabs', $adminTabs);
			}
			
		}
		
		
		
		
		return $page->makeDisplay();
	}	
	
	
	protected function processAction($actionResults)
	{
		$get = Get::getInstance();
		$processedOutput = new DisplayMaker();
		$page = ActivePage::get_instance();
		
		if($get['ajax'])
		{
			
		}else{
			
			$themePath = $page->getThemePath();
			$themePath .= 'adminContent.html';
			$text = file_get_contents($themePath);
			$processedOutput->set_display_template($text);
			
			/*
			add sidebar
			*/
			
			/*
			add tabs
			*/
			
		}

		$title = (isset($this->main_action->AdminSettings['headerTitle'])) ? $this->main_action->AdminSettings['headerTitle'] : '';
		$subTitle = (isset($this->main_action->AdminSettings['headerSubTitle'])) ? $this->main_action->AdminSettings['headerSubTitle'] : '';
		$processedOutput->addContent('content', $actionResults);
		$processedOutput->addContent('title', $title);
		$processedOutput->addContent('subtitle', $subTitle);
		
		$page = ActivePage::get_instance();
		$page->addRegion('content', $processedOutput->make_display(false));
	}

	
	
}


?>