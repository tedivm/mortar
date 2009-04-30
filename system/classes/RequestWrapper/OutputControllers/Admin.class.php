<?php

class AdminOutputController extends AbstractOutputController
{
	protected function start()
	{
		$site = ActiveSite::getSite();
		$siteLocation = $site->getLocation();

		$page = ActivePage::getInstance();
		$page->addRegion('title', 'BentoBase Admin');
		$page->setTemplate('index.html', $siteLocation->getMeta('adminTheme'));

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
		$user = ActiveUser::getInstance();
		$userId = $user->getId();
		$action = $adminController->getAction();
		$tabs = $this->loadLinks();
		$activeTab = isset($action->adminSettings['tab']) ? $action->adminSettings['tab'] : 'Main';
		$sidebar = new HtmlObject('div');
		$sidebar->id = 'BB_left_sidebar';
		$sidebar->addClass('BB_sidebar');
		$activeNav = $tabs[$activeTab];
		if(is_array($activeNav))
			foreach($activeNav as $container => $links)
		{
			if(count($links) > 0)
			{
				$div = $sidebar->insertNewHtmlObject('div');
				$div->addClass('BB_sidebar_menu');


				if($container != 'StandAlone')
					$div->insertNewHtmlObject('h2')->
						wrapAround($container);

				$ul = $div->insertNewHtmlObject('ul');

				foreach($links as $link)
				{
					$a = $link['url']->getLink($link['label']);
					$li = $ul->insertNewHtmlObject('li')
						->addClass('BB_sidebar_menu')->
						wrapAround($a);
				}
				$li->addClass('last');
			}
		}
		$page = $adminController->getResource();
		$page['navbar'] = (string) $sidebar;








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
					if($this->checkLinkPermission($link['url'], $userId))
						$newLinks[] = $link;
				}
				if(count($newLinks) > 0)
					$processedLinks[$tab][$container] = $newLinks;
			}
		}
		return $processedLinks;
	}

	protected function checkLinkPermission($url, $userId)
	{

		if(isset($url->locationId))
		{
			$action = (isset($url->action)) ? $url->action : 'Read';

			$location = new Location($url->locationId);
			$resource = $location->getResource();
			$actionName = $resource->getAction($action);
			$requiredPermission = staticHack($actionName, 'requiredPermission');

			$permission = new Permissions($url->locationId, $userId);
			return $permission->isAllowed($requiredPermission);

		}elseif(isset($url->module)){
			$permissionList = new PermissionLists($userId);

			$actionName = importFromModule($url->action, $url->module, 'action');

			$permission = staticHack($actionName, 'requiredPermission');
			$permissionType = staticHack($actionName, 'requiredPermissionType');

			if(!$permission)
				$permission = 'execute';

			if(!$permissionType)
				$permissionType = 'base';

			if(!$permissionList->checkAction($permissionType, $permission))
			{
				return false;
			}
		}

		// check permissions
		if(isset($link['permissionSet']))
		{
			if(isset($link['location']))
			{
				$permission = new Permissions($link['location'], $userId);
				if(!$permission->isAllowed($link['permissionSet']['action'],
												$link['permissionSet']['type']))
				{
					return false;
				}
			}else{
				$permissionList = new PermissionLists($userId);
				if(!$permissionList->checkAction($link['permissionSet']['type'],
												$link['permissionSet']['action']))
				{
					return false;
				}
			}
		}
		return true;
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