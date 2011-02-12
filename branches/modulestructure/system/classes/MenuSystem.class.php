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

	public function addItem($menu, $item, $name, $sort = null, $perm = null)
	{
		if(!isset($this->menus[$menu]))
			$this->menus[$menu] = new Menu($menu);

		$curMenu = $this->menus[$menu];
		$curMenu->addItem($item, $name, $sort, $perm);
	}

	public function addItemToSubmenu($menu, $submenu, $item, $name, $sort = null, $perm = null)
	{
		if(!isset($this->menus[$menu]))
			$this->menus[$menu] = new Menu($menu);

		$curMenu = $this->menus[$menu];
		$curMenu->addItemToSubmenu($submenu, $item, $name, $sort, $perm);
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
		$url = (string) new Url();
		$link = new HtmlObject('a');

		$installerUrl = $url . '?action=Install';
		$installerLink = clone $link;
		$installerLink->property('href', $installerUrl);
		$installerLink->wrapAround('Install');
		$this->addItemToSubmenu('primary', 'Installation', (string) $installerLink, 'Install');

		$requirementsUrl = $url . '?action=Requirements';
		$requirementsLink = clone $link;
		$requirementsLink->property('href', $requirementsUrl);
		$requirementsLink->wrapAround('Check Requirements');
		$this->addItemToSubmenu('primary', 'Installation', (string) $requirementsLink, 'Check Requirements');

		$htaccessUrl = $url . '?action=htaccess';
		$htaccessLink = clone $link;
		$htaccessLink->property('href', $htaccessUrl);
		$htaccessLink->wrapAround('.htaccess File');
		$this->addItemToSubmenu('primary', 'Installation', (string) $htaccessLink, '.htaccess File');
	}

}

?>