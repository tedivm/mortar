<?php

class AdminOutputController extends AbstractOutputController
{
	protected function start()
	{
		$page = ActivePage::getInstance();
		$page->addRegion('title', 'BentoBase Admin');

		$this->activeResource = $page;

		// Add filter to fit content into adminContent sub templates
		$contentFilter = new AdminControllerContentFilter();
		$this->addContentFilter($contentFilter);

		if(INSTALLMODE)
		{
			$navigation = new AdminControllerResourceFilterInstallerNavigation();
			$this->addOutputFilter($navigation);
			$page->setTemplate('index.html', 'bbAdmin');
		}else{


			$site = ActiveSite::getSite();
			$siteLocation = $site->getLocation();
			$page->setTemplate('index.html', $siteLocation->getMeta('adminTheme'));


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
		$user = ActiveUser::getInstance();
		$userId = $user->getId();
		$action = $adminController->getAction();
		$tabs = $this->loadLinks();
		$activeTab = isset($action->adminSettings['tab']) ? $action->adminSettings['tab'] : 'Main';


		$newNav = new NavigationMenu('left');



		$newNav->setMenu('menuName');
		$newNav->setMenuLabel('menuLabel');
		$newNav->addItem('name', 'url', 'label');


		$activeNav = isset($tabs[$activeTab]) ? $tabs[$activeTab] : 'main';

		$navbar = new NavigationMenu('left');
		if(is_array($activeNav))
			foreach($activeNav as $container => $links)
		{
			if(count($links) > 0)
			{
				$navbar->setMenu($container);
				$navbar->setMenuLabel($container);

				foreach($links as $link)
				{
					$navbar->addItem(preg_replace('[^A-Za-z0-9]', '', $link['label']), $link['url'], $link['label']);
				}
			}
		}

		$page = $adminController->getResource();
		$page['navbar'] = $navbar->makeDisplay();


		$tabUl = new HtmlObject('ul');
		$tabUl->id = 'top-navigation';
		$tabNames = array_keys($tabs);

		$url = new Url();
		$url->format = 'Admin';

		unset($tabNames[array_search('Main', $tabNames)]);
		array_unshift($tabNames, 'Main');
		foreach($tabNames as $tab)
		{
			if($tab == 'Universal')
				continue;

			$li = $tabUl->insertNewHtmlObject('li');
			if($tab == $activeTab)
				$li->addClass('active');

			$li->addClass('BB_tool_box');
			$url->tab = $tab;

			$li->insertNewHtmlObject('a')->
				property('href', (string) $url)->
				wrapAround($tab);
		}

		$page['navtabs'] = (string) $tabUl;
	}

	protected function loadLinks()
	{
		$cache = new Cache('admin', 'navigation', 'rawLinks');
		$links = $cache->getData();

		$hook = new Hook('system', 'adminInterface', 'navigation');
		$tabResults = $hook->getTabs();

		$tabs = call_user_func_array('array_merge_recursive', $tabResults);

		if(!$cache->cacheReturned)
		{
			$links = array();
			if(count($tabs) > 0)
			{
				foreach($tabs as $tab)
				{
					$navResults = $hook->getStaticNav($tab);
					if(count($navResults) > 0)
					{
						$link = call_user_func_array('array_merge_recursive', $navResults);
						if(count($link) > 0)
							$links[$tab] = $link;
					}
				}
			}

			$cache->storeData($links);
		}

		$processedLinks = $this->processLinks($links);

		if(count($tabs) > 0)
		{
			$links = array();
			foreach($tabs as $tab)
			{
				$navResults = $hook->getDynamicNav($tab);
				if(count($navResults) > 0)
				{
					$link = call_user_func_array('array_merge_recursive', $navResults);
					if(count($link) > 0)
						$links[$tab] = $link;
				}
			}

			$processedLinks = array_merge_recursive($processedLinks, $this->processLinks($links));
		}

		return $processedLinks;
	}

	protected function processLinks($unprocessedLinks)
	{
		$user = ActiveUser::getInstance();
		$userId = $user->getId();
		$processedLinks = array();
		foreach($unprocessedLinks as $tab => $containers)
		{
			foreach($containers as $container => $links)
			{
				$newLinks = array();
				foreach($links as $link)
				{
					if($link['url']->checkPermission($userId))
						$newLinks[] = $link;
				}
				if(count($newLinks) > 0)
					$processedLinks[$tab][$container] = $newLinks;
			}
		}
		return $processedLinks;
	}
}


class AdminControllerResourceFilterInstallerNavigation
{

	public function update($adminController)
	{
		$page = $adminController->getResource();
		$page['navbar'] = '
   <div id="left_sidebar_menu" class="sidebar">
      <div class="sidebar_menu">
         <h2>Menu</h2>
         <ul>
            <li class="sidebar_menu last">
               <a href="#">Menu Item</a>
            </li>
         </ul>
      </div>
   </div>
'; // $adminNav->getLinks($tab);



		$page['navtabs'] = '   <ul id="top-navigation">
      <li class="active BB_tool_box">
         <a href="#">Tab</a>
      </li>

      <li class="BB_tool_box">
         <a href="#">Tab</a>
      </li>

      <li class="BB_tool_box">
         <a href="#">Tab</a>
      </li>

   </ul>
'; // $adminNav->getTabs($tab);


	}
}


?>