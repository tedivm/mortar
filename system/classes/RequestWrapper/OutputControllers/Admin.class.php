<?php

class AdminOutputController extends AbstractOutputController
{
	protected function start()
	{
		$page = ActivePage::getInstance();
		$page->addRegion('title', 'BentoBase Admin');
		$page->setTemplate('index.html', 'admin');

		$this->activeResource = $page;

		// Add filter to fit content into adminContent sub templates
		$contentFilter = new AdminControllerContentFilter();
		$this->addContentFilter($contentFilter);

		if(INSTALLMODE)
		{
			$navigation = new AdminControllerResourceFilterInstallerNavigation();
			$this->addOutputFilter($navigation);
		}else{

			// This filter adds our navigational bars
			$navigation = new AdminControllerResourceFilterNavigation();
			$this->addOutputFilter($navigation);
		}
	}

	protected function bundleOutput($output)
	{
		$this->activeResource->addRegion('content', $output);
	}

	protected function makeDisplayFromResource()
	{
		return $this->activeResource->makeDisplay();
	}

}

// content filters

class AdminControllerContentFilter
{
	public function update($adminController, $output)
	{
		$action = $adminController->getAction();
		$page = $adminController->getResource();

		$themePath = $page->getThemePath();
		$themePath .= 'adminContent.html';
		$text = file_get_contents($themePath);

		$title = (isset($action->AdminSettings['headerTitle'])) ? $action->AdminSettings['headerTitle'] : '';
		$subTitle = (isset($action->AdminSettings['headerSubTitle'])) ? $action->AdminSettings['headerSubTitle'] : '';

		$processedOutput = new DisplayMaker();
		$processedOutput->setDisplayTemplate($text);
		$processedOutput->addContent('content', $output);
		$processedOutput->addContent('title', $title);
		$processedOutput->addContent('subtitle', $subTitle);
		$output = $processedOutput->makeDisplay();

		return $output;
	}
}

// resource filters

class AdminControllerResourceFilterNavigation
{

	public function update($adminController)
	{
		$action = $adminController->getAction();
		$page = $adminController->getResource();

		$info = InfoRegistry::getInstance();

		if(class_exists('AdminNavigation', false)
			|| (include($info->Configuration['path']['mainclasses'] . 'AdminNavigation.class.php')
				|| class_exists('AdminNavigation', false) ) )
		{
			$adminNav = new AdminNavigation();
			$tab = ($action->AdminSettings['linkTab']) ? $action->AdminSettings['linkTab'] : 'Main';
			$page['navbar'] = $adminNav->getLinks($tab);
			$page['navtabs'] = $adminNav->getTabs($tab);

		}
	}
}


class AdminControllerResourceFilterInstallerNavigation
{

	public function update($adminController)
	{
		$page = $adminController->getResource();
		$page['navbar'] = '<div id="left-column">
	<div><h3>Container Name</h3>
		<ul class="nav">

			<li><a href="#">Item</a></li>
			<li class="last"><a href="#">Item</a></li>
		</ul>
	</div>
</div>'; // $adminNav->getLinks($tab);



		$page['navtabs'] = '<ul id="top-navigation">
	<li><span><span><a href="#">Tab</a></span></span></li>
	<li><span><span><a href="#">Tab</span></span></a></li>
</ul>'; // $adminNav->getTabs($tab);


	}
}


?>