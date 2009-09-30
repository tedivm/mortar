<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage RequestWrapper
 */

/**
 * This class takes an action, runs it, and then formats the output for the admin side of the website.
 *
 * @package System
 * @subpackage RequestWrapper
 */
class AdminOutputController extends AbstractOutputController
{
	/**
	 * On start set the active resource to the ActivePage, set the theme, and add the navigation filters.
	 *
	 */
	protected function start()
	{
		$page = ActivePage::getInstance();
		$page->addRegion('title', 'Mortar Admin');

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

	/**
	 * This function takes a string (the output of the action) and gives it to the ActivePage as a replacement for the
	 * 'content' section of the theme template.
	 *
	 * @param string $output
	 */
	protected function bundleOutput($output)
	{
		$this->activeResource->addRegion('content', $output);
	}

	/**
	 * This takes the activeResource (in this case the ActivePage) and returns the display of that page.
	 *
	 * @return string Html formatted
	 */
	protected function makeDisplayFromResource()
	{
		return $this->activeResource->makeDisplay();
	}

}

// content filters

/**
 * This class expands and allows for customization of the content that gets sent back by the running action.
 *
 * @package System
 * @subpackage RequestWrapper
 */
class AdminControllerContentFilter
{
	/**
	 * This takes the content area and expands it to include more information, such as the title and subtitle, of
	 * the action. The structure for that expanded display is written to the 'adminContent.html' theme file.
	 *
	 * @param OutputController $adminController
	 * @param string $output
	 * @return string
	 */
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

/**
 * This class handles the admin interface's navigation.
 *
 * @package System
 * @subpackage RequestWrapper
 */
class AdminControllerResourceFilterNavigation
{
	/**
	 * This function takes the adminController, uses it to get the active resource and other information, and uses that
	 * to build nagivation menus.
	 *
	 * @param ouputController $adminController
	 */
	public function update($adminController)
	{
		$user = ActiveUser::getUser();
		$userId = $user->getId();
		$action = $adminController->getAction();
		$tabs = $this->loadLinks();
		$activeTab = isset($action->adminSettings['tab']) ? $action->adminSettings['tab'] : 'Main';



		$page = $adminController->getResource();


		$activeNav = isset($tabs[$activeTab]) ? $tabs[$activeTab] : 'main';
		$navbar = new NavigationMenu('left');

		if(is_array($activeNav))
			foreach($activeNav as $container => $links)
		{
			if(count($links) > 0)
			{
				$navbar = $page->getMenu($container, 'left');
				$navbar->setMenuLabel($container);

				foreach($links as $link)
				{
					$navbar->addItem(preg_replace('[^A-Za-z0-9]', '', $link['label']), $link['url'], $link['label']);
				}
			}
		}

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

	/**
	 * This function loads all of the possible links the admin navigation could use. It accomplishes this through the
	 * use of the Hook/Plugin system. The results are cached for performance.
	 *
	 * @hook system adminInterface navigation
	 * @cache admin navigation rawLinks
	 * @return array Links that can be used in the navigation menu
	 */
	protected function loadLinks()
	{
		$cache = new Cache('admin', 'navigation', 'rawLinks');
		$links = $cache->getData();

		$hook = new Hook();
		$hook->loadPlugins('system', 'adminInterface', 'navigation');
//		$hook = new Hook('system', 'adminInterface', 'navigation');
		$tabResults = $hook->getTabs();

		$tabs = call_user_func_array('array_merge_recursive', $tabResults);

		if($cache->isStale())
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

	/**
	 * This function filters out links that point to an action the user can not perform.
	 *
	 * @param array $unprocessedLinks
	 * @return array
	 */
	protected function processLinks($unprocessedLinks)
	{
		$user = ActiveUser::getUser();
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

/**
 * This class handles the installer interface's navigation. Currently it is pretty damn useless.
 *
 * @package System
 * @subpackage RequestWrapper
 * @todo Make this not useless.
 */
class AdminControllerResourceFilterInstallerNavigation
{
	/**
	 * This takes in the adminController and adds navigation to the installer. This current navigation sucks.
	 *
	 * @param ouputController $adminController
	 */
	public function update($adminController)
	{
		$page = $adminController->getResource();

	//	$page = new Page();
	//	$menu = $page->getMenu('installer');

	//	$menu->setMenuLabel('Installation Links');

		$url = new Url();
		$url->format = 'admin';
		$url->module = 'Installer';

		$requirementUrl = clone $url;
		$requirementUrl->action = 'Requirements';

		$installerUrl = clone $url;
		$installerUrl->action = 'Install';


		$page['__navbar_1'] = '
   <div id="main_sidebar_menu" class="sidebar">
      <div class="sidebar_menu">
         <h2>Installation</h2>
         <ul>
            <li class="sidebar_menu last">
               ' . $requirementUrl->getLink('Check Requirements') . '
            </li>
            <li class="sidebar_menu last">
               ' . $installerUrl->getLink('Install') . '
            </li>
         </ul>
      </div>
   </div>
';

		$page['__navbar_2'] = '
<!--   <div id="modelNav_sidebar_menu" class="sidebar">
      <div class="sidebar_menu">
         <h2>Menu</h2>
         <ul>
            <li class="sidebar_menu last">
               <a href="#">Menu Item</a>
            </li>
         </ul>
      </div>
   </div>

-->
';



		$page['navtabs'] = '   <ul id="top-navigation">
		<!--
      <li class="active BB_tool_box">
         <a href="#">Tab</a>
      </li>

      <li class="BB_tool_box">
         <a href="#">Tab</a>
      </li>

      <li class="BB_tool_box">
         <a href="#">Tab</a>
      </li>
		-->
   </ul>
'; // $adminNav->getTabs($tab);


	}
}


?>