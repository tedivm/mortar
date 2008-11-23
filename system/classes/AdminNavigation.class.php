<?php

Class AdminNavigation
{
	protected $tabs = array();
	
	public function __construct()
	{
		
		$cache = new Cache('packages', 'adminTabs');
		
		$tabs = $cache->get_data();
		
		if(!$cache->cacheReturned)
		{
			$db = db_connect('default_read_only');
			$packageResults = $db->query('SELECT mod_id, location_id, mod_package FROM modules');
			
			while($module = $packageResults->fetch_array())
			{
				$packages[$module['mod_package']]['modules'][] = array('modId' => $module['mod_id'], 'locationId' => $module['location_id']);
			}
			

			// this forces the default order, 
			$tabs = array_fill_keys(array('Universal', 'Main', 'System'), array());
			
			foreach($packages as $package => $modules)
			{
				
				if(!$packageInfo[$package]  instanceof PackageInfo)
					$packageInfo[$package] = new PackageInfo($package);
				
				if(is_array($packageInfo[$package]->actions))
				foreach($packageInfo[$package]->actions as $name => $action)
				{
					if($action['engineSupport']['Admin']['settings']['linkLabel'] && $action['engineSupport']['Admin']['settings']['linkTab'])
					{
						$linkInfo = array();
						$linkInfo['installedModules'] = $packageInfo->installedModules;
						$linkInfo['linkLabel'] = $action['engineSupport']['Admin']['settings']['linkLabel'];
						$linkInfo['linkTab'] = $action['engineSupport']['Admin']['settings']['linkTab'];
						$linkInfo['package'] = $package;
						$linkInfo['permission'] = $action['permissions'];
						$linkInfo['action'] = $name;
						
						$url = new Url();
						$url->engine = 'Admin';
						$url->action = $name;
						
						if($action['type'] == 'genericPackage')
						{
							$url->package = $packageInfo[$package]->getName();
						}elseif($action['type'] == 'specificModule'){
							
						}
						
						$linkInfo['url'] = (string) $url;

						if($action['engineSupport']['Admin']['settings']['linkContainer'])
						{
							$tabs[$action['engineSupport']['Admin']['settings']['linkTab']][$action['engineSupport']['Admin']['settings']['linkContainer']][] = $linkInfo;
						}else{
							$tabs[$action['engineSupport']['Admin']['settings']['linkTab']][] = $linkInfo;
						}
						 
		
					}
				}				
				
			}

			foreach($tabs as $tabName => $tabContents)
			{
				if(count($tabContents) < 1)
					unset($tabs[$tabName]);
			}
			
			$cache->store_data($tabs);
		}

		$user = ActiveUser::getInstance();
		$cleanTabCache = new Cache('users', $user->getId(), 'adminNav', 'sidebar');
		
		$cleanTabs = $cleanTabCache->getData();
		
		if(!$cleanTabCache->cacheReturned)
		{
			$user = ActiveUser::getInstance();

			if($user->getName() == 'guest')
			{
				$cleanArray = array('LogOut' => 'BentoBase');
			}else{
				$cleanArray = array('LogIn' => 'BentoBase');
			}
			
			foreach($tabs as $tabName => $tabLinks)
			{
				$cleanTabs[$tabName] = $this->cleanLinks($tabLinks, $cleanArray);
			}			
			
			foreach($cleanTabs as $tabName => $tabContents)
			{
				if(count($tabContents) < 1)
					unset($cleanTabs[$tabName]);
			}
			
			$cleanTabCache->storeData($cleanTabs);
		}

		$this->tabs = $cleanTabs;
	}
	
	protected function cleanLinks($linkGroup, $removeActions = false)
	{
		$cleanedLinkGroup = array();
		$cleanedLinkSpares = array();
		foreach($linkGroup as $name => $links)
		{
			if(!is_numeric($name))
			{
				
				foreach($links as $link)
				{
					if($this->checkLink($link, $removeActions))
						$cleanedLinkGroup[$name][] = $link;
				}
				
			}else{
				if($this->checkLink($links, $removeActions))
					$cleanedLinkSpares[] = $links;
			}
		}
		
		$output = array_merge($cleanedLinkGroup, $cleanedLinkSpares);
		return $output;
	}
	
	protected function checkLink($link, $removeActions)
	{
		if(array_key_exists($link['action'], $removeActions) && $link['package'] == $removeActions[$link['action']])
			return false;
			
		$packageInfo = new PackageInfo($link['package']);
		return $packageInfo->checkAuth($link['permission']);
	}
	
	public function getTabs($activeTab = '')
	{
		if($activeTab == 'Universal')
			$activeTab = 'Main';
			
			
		$tabList = $this->tabs;
		unset($tabList['Universal']);
		$tabs = array_keys($tabList);
		
		$tabList = new HtmlObject('ul');
		$tabList->property('id', 'top-navigation');
				
		foreach($tabs as $tabName)
		{
			$item = $tabList->insertNewHtmlObject('li');
			
			if($tabName == $activeTab)
				$item->addClass('active');
			
			$url = new Url();
			$url->property('engine', 'Admin')->
				property('tab', $tabName);	
				
			$item->insertNewHtmlObject('span')->
				insertNewHtmlObject('span')->
				insertNewHtmlObject('a')->
					property('href',(string) $url)->
					wrapAround($tabName);
		}
		

		return $tabList;
	}
	
	
	public function getLinks($tab)
	{
		if($tab == 'Universal')
			$tab = 'Main';
		

			
		$activeLayer = is_array($this->tabs[$tab]) ? $this->tabs[$tab] : array();
		
		if(is_array($this->tabs['Universal']) && $tab != 'Universal')
			$activeLayer = array_merge_recursive($activeLayer, $this->tabs['Universal']);
			
			
		$navBar = new HtmlObject('div');
		$navBar->property('id', 'left-column');
		
		//var_dump($activeLayer);
		
		foreach($activeLayer as $name => $item)
		{


			if(!is_numeric($name))
			{
				
				$container = new HtmlObject('div'); 
				$container->insertNewHtmlObject('h3')
					->wrapAround($name);
				
				$linkList = $container->insertNewHtmlObject('ul')
					->addClass('nav');
				
					
				$hasActions = false;
				
				foreach ($item as $action)
				{
					$hasActions = true;
					$linkListCurrent = $linkList->insertNewHtmlObject('li');
					$linkListCurrent->insertNewHtmlObject('a')->
						property('href', $action['url'])->
						wrapAround($action['linkLabel']);
				}

				$linkListCurrent->addClass('last');
				$navBar->wrapAround($container);

				
			}else{
				
				$packageInfo = new PackageInfo($item['package']);
				if($packageInfo->checkAuth($item['permission']))
				{				
					$navBar->insertNewHtmlObject('a')->
							addClass('link')->
							property('href', $item['url'])->
							wrapAround($item['linkLabel']);
				}
			}
		}
		
		return $navBar;
		
	}
}

?>