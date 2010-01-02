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
 * This class represents a single display menu.
 *
 * @package System
 */
class Menu
{
	/**
	 * The name of this menu.
	 *
	 * @var array
	 */

	protected $name;

	/**
	 * The menu items currently included in this menu.
	 *
	 * @var array
	 */
	protected $menuItems = array();

	/**
	 * The highest sort number currently used in this menu
	 *
	 * @var int
	 */
	protected $highSort = 10;

	/**
	 * Whether the menu needs to be sorted before it's returned.
	 *
	 * @var array
	 */
	protected $shouldSort = false;

	/**
	 * Create the menu, passing a name and optionally an array of items
	 *
	 * @param string $name
	 */
	public function __construct($name)
	{
		$this->name = $name;
	}

	/**
	 * Add an array of items
	 *
	 * @param string|Menu $item
	 * @param string $name
	 * @param int|null $location
	 */
	public function addItem($item, $name, $location = null)
	{
		$menuItem = array('name' => $name, 'menu' => $this->name);
		if ($item instanceof Menu) {
			$menuItem['isMenu'] = true;
			$menuItem['item'] = $item;
			$this->submenus[$name] = $item;
		} else {
			$menuItem['isMenu'] = false;
			$menuItem['item'] = (string) $item;
		}

		if(isset($location)) {
			$menuItem['sort'] = $location;
			if($location > $this->highSort)
				$this->highSort = $location;			
		} else {
			$menuItem['sort'] = ++$this->highSort;
		}

		$this->shouldSort = true;
		$this->menuItems[$name] = $menuItem;
	}

	/**
	 * Add an item to a named submenu
	 *
	 * @param string $submenu
	 * @param string|Menu $item
	 * @param string $name
	 * @param int|null $location
	 */
	public function addItemToSubmenu($submenu, $item, $name, $location = null)
	{
		if (!isset($this->menuItems[$submenu]))
			$this->addItem(new Menu($submenu), $submenu);
		elseif (!$this->menuItems[$submenu]['isMenu'])
			return false;

		$menu = $this->submenus[$submenu];
		$menu->addItem($item, $name, $location);
	}

	/**
	 * Sorts the current list of menu items by priority and then by name.
	 *
	 * @param string|Menu $item
	 */
	public function sort()
	{
		$sort = array();
		$name = array();

		foreach($this->menuItems as $row) {
			$sort[] = $row['sort'];
			$name[] = $row['name'];
		}

		array_multisort($sort, SORT_ASC, $name, SORT_ASC, $this->menuItems);
	}

	/**
	 * Returns the full list of current menu items, sorting the menu first if an item has been added since the
	 * last time this menu was sorted.
	 *
	 * @param array $items
	 */
	public function getItems()
	{
		if ($this->shouldSort) {
			$this->shouldSort = false;
			$this->sort();
		}
		return $this->menuItems;
	}

	/**
	 * Returns the name of this menu.
	 *
	 */
	public function getName()
	{
		return $this->name;
	}
}

?>