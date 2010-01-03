<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Caching
 */

/**
 * This class manages all menu instances which are in play for the current page.
 *
 * @package System
 */
class MenuSystem
{
	/**
	 * The array of current menus.
	 *
	 * @var array
	 */
	protected $menus = array();

	/**
	 * Create initial menus by loading any menus or menu items which are to be loaded from plugins.
	 *
	 */
	public function initMenus(Model $model = null)
	{
		$query = Query::getQuery();
	
		$hook = new Hook();
		$hook->loadPlugins('system', 'menus', $query['format']);
		$hook->addMenuItems($this);

		if(isset($model)) {
			$hook = new Hook();
			$hook->loadModelPlugins($model, $query['format'] . 'Menu'); 
			$hook->addModelMenuItems($this, $model);
		}
	}

	public function addItem($menu, $item, $name, $location = null)
	{
		if(!isset($this->menus[$menu]))
			$this->menus[$menu] = new Menu($menu);

		$curMenu = $this->menus[$menu];
		$curMenu->addItem($item, $name, $location);
	}

	public function addItemToSubmenu($menu, $submenu, $item, $name, $location = null)
	{
		if(!isset($this->menus[$menu]))
			$this->menus[$menu] = new Menu($menu);

		$curMenu = $this->menus[$menu];
		$curMenu->addItemToSubmenu($submenu, $item, $name, $location);	
	}

	public function removeItem($menu, $name)
	{
		if(!isset($this->menus[$menu]))
			return false;

		$curMenu = $this->menus[$menu];
		$curMenu->removeItem($name);
	}

	public function removeItemFromSubmenu($menu, $submenu, $name)
	{
		if(!isset($this->menus[$menu]))
			return false;

		$curMenu = $this->menus[$menu];
		$curMenu->removeItemFromSubmenu($submenu, $name);
	}

	public function getMenu($menu)
	{
		return isset($this->menus[$menu]) ? $this->menus[$menu] : false;
	}

	public function getMenus()
	{
		return $this->menus;
	}

	public function getMenuNames()
	{
		$menuNames = array();

		foreach($this->menus as $menu)
			$menuNames[] = $menu->getName();

		return $menuNames;
	}

	public function installMode()
	{
		$url = new Url();
		$url->module = 'Installer';

		$installerUrl = clone $url;
		$installerUrl->action = 'Install';
		$link = $installerUrl->getLink('Install');
		$this->addItemToSubmenu('primary', 'Installation', $link, 'Install');

		$requirementUrl = clone $url;
		$requirementUrl->action = 'Requirements';
		$link = $requirementUrl->getLink('Check Requirements');
		$this->addItemToSubmenu('primary', 'Installation', $link, 'Check Requirements');

		$htaccessUrl = clone $url;
		$htaccessUrl->action = 'htaccess';
		$link = $htaccessUrl->getLink('htaccess File');
		$this->addItemToSubmenu('primary', 'Installation', $link, 'htaccess File');	
	}

}

?>