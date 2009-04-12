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

		$user = ActiveUser::getInstance();
		$userId = $user->getId();
		$action = $adminController->getAction();
		$unprocessedLinks = $this->loadLinks();
		$tabs = $this->processLinks($unprocessedLinks);

		$activeTab = isset($action->adminSettings['tab']) ? $action->adminSettings['tab'] : 'Main';

		$sidebar = new HtmlObject('div');
		$sidebar->id = 'left-column';

		$activeNav = $tabs[$activeTab];
		foreach($activeNav as $container => $links)
		{
			$div = $sidebar->insertNewHtmlObject('div');
			$div->insertNewHtmlObject('h3')->
				wrapAround($container);

			$ul = $div->insertNewHtmlObject('ul');
			$ul->addClass('nav');
			foreach($links as $link)
			{
				$a = $link['url']->getLink($link['label']);
				$li = $ul->insertNewHtmlObject('li')->
				wrapAround($a);
			}
			$li->addClass('last');
		}
		$page = $adminController->getResource();
		$page['navbar'] = (string) $sidebar;

		$tabUl = new HtmlObject('ul');
		$tabUl->id = 'top-navigation';
		$tabNames = array_keys($tabs);
		foreach($tabNames as $tab)
		{
			$li = $tabUl->insertNewHtmlObject('li');
			if($tab == $activeTab)
				$li->addClass('active');

			$li->insertNewHtmlObject('span')->
				insertNewHtmlObject('span')->
				insertNewHtmlObject('a')->
				wrapAround($tab);
		}

		$page['navtabs'] = (string) $tabUl;
	}


	protected function loadLinks()
	{
		$cache = new Cache('admin', 'navigation', 'rawLinks');
		$links = $cache->getData();

		if(!$cache->cacheReturned)
		{
			$links = array();
			$hook = new Hook('system', 'adminInterface', 'navigation');
			$tabs = call_user_func_array('array_merge_recursive', $hook->getTabs());

			foreach($tabs as $tab)
			{
				$link = call_user_func_array('array_merge_recursive', $hook->getNav($tab));
				if(count($link) > 0)
					$links[$tab] = $link;
			}
			$cache->storeData($links);
		}
		return $links;
	}

	protected function processLinks($unprocessedLinks)
	{
		$user = ActiveUser::getInstance();
		$userId = $user->getId();

		$cache = new Cache('admin', 'navigation', 'processedLinks', $userId);
		$processedLinks = $cache->getData();

		if(!$cache->cacheReturned)
		{
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
					$processedLinks[$tab][$container] = $newLinks;
				}
			}
			$cache->storeData($processedLinks);
		}
		return $processedLinks;
	}


	protected function checkLinkPermission($url, $userId)
	{

		if(isset($url->locationId))
		{
			$permission = new Permissions($url->locationId, $userId);
			$action = (isset($url->action)) ? $url->action : 'Read';
			return $permission->isAllowed($action);

		}elseif(isset($url->module)){

			$permissionList = new PermissionLists($userId);
			$packageInfo = new PackageInfo($url->module);

			$permissionList = new PermissionLists($userId);

			if(!$permissionList->checkAction('type', 'action'))
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
	</div>\
</div>'; // $adminNav->getLinks($tab);



		$page['navtabs'] = '<ul id="top-navigation">
	<li><span><span><a href="#">Tab</a></span></span></li>
	<li><span><span><a href="#">Tab</span></span></a></li>
</ul>'; // $adminNav->getTabs($tab);


	}
}


?>