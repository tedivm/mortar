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
	protected $menuItems;

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
	 */
	public function addItem($item, $name, $location = null);
	{
		$menuItem = array('name' => $name);
		if ($item instanceof Menu) {
			$menuItem['isMenu'] = true;
			$menuItem['item'] = $item;
		} else {
			$menuItem['isMenu'] = false;
			$menuItem['item'] = (string) $item;
		}
			
		(isset($location) && is_numeric($location))
			? array_splice($this->menuItems, $location, 0, array($menuItem))
			: $this->menuItems[] = $menuItem;
	}

	/**
	 * Sorts the current list of menu items by name
	 *
	 * @param string|Menu $item
	 */
	public function sort()
	{
		foreach($this->menuItems as $key => $row)
			$name[$key] = $row['name'];

		array_multisort($name, SORT_ASC, $this->menuItems);
	}

	/**
	 * Return the full list of current menu items
	 *
	 * @param array $items
	 */
	public function getItems()
	{
		return $this->menuItems;
	}
}

?>