<?php

class AdminEngine extends Engine
{
	public $engine_type = 'Admin';
	public $default_action = 'MainDisplay';
	protected $requiredPermission = 'Admin';


	protected function startEngine()
	{
		$this->returnMode = 'full';
		$page = ActivePage::getInstance();
		$page->addRegion('title', 'BentoBase Admin');
		$page->setTemplate('index.html', 'admin');
	}

	public function display()
	{
		$page = ActivePage::getInstance();
		$page->addRegion('PathToPackage', $this->main_action->info['PathToPackage']);
		$info = InfoRegistry::getInstance();

		if(class_exists('AdminNavigation', false) || include($info->Configuration['path']['mainclasses'] . 'AdminNavigation.class.php'))
		{
			$adminNav = new AdminNavigation();
			$tab = ($this->main_action->AdminSettings['linkTab']) ? $this->main_action->AdminSettings['linkTab'] : 'Main';
			$adminLinks = $adminNav->getLinks($tab);
			$adminTabs = $adminNav->getTabs($tab);
			$page->addRegion('navbar', $adminLinks);
			$page->addRegion('navtabs', $adminTabs);
		}

		return $page->makeDisplay();
	}

	protected function processAction($actionResults)
	{
		$get = Get::getInstance();
		$processedOutput = new DisplayMaker();
		$page = ActivePage::getInstance();

		$themePath = $page->getThemePath();
		$themePath .= 'adminContent.html';
		$text = file_get_contents($themePath);
		$processedOutput->set_display_template($text);

		$title = (isset($this->main_action->AdminSettings['headerTitle'])) ? $this->main_action->AdminSettings['headerTitle'] : '';
		$subTitle = (isset($this->main_action->AdminSettings['headerSubTitle'])) ? $this->main_action->AdminSettings['headerSubTitle'] : '';
		$processedOutput->addContent('content', $actionResults);
		$processedOutput->addContent('title', $title);
		$processedOutput->addContent('subtitle', $subTitle);

		$page = ActivePage::getInstance();
		$page->addRegion('content', $processedOutput->make_display(false));
	}



}


?>