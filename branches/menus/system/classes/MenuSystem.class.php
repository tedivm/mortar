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
		$hook->loadPlugins('menus', $query['format'], 'base');
		if(isset($model))
			$hook->loadPlugins('menus', $query['format'], $model->getType());

		$menuItems = $hook->getMenuItems();
		
		foreach($menuItems as $item) {
			$loc = isset($item['location']) ? $item['location'] : null;
			$this->addItem($item['menu'], $item['item'], $item['name'], $loc);
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

}

?>