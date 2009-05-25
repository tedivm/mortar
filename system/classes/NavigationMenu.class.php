<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Display
 */

/**
 * This class creates navigation menus
 *
 * @package System
 * @subpackage Display
 */
class NavigationMenu
{

	/**
	 * This is the name of the menu being built
	 *
	 * @access protected
	 * @var string
	 */
	protected $menuName;

	/**
	 * An array of labels for the sub menus
	 *
	 * @access protected
	 * @var array
	 */
	protected $subMenuLabels = array();

	/**
	 * This is the current active sub menu
	 *
	 * @access protected
	 * @var string
	 */
	protected $activeSubMenu;

	/**
	 * This is where all the information for each sub menu is stored
	 *
	 * @access protected
	 * @var array
	 */
	protected $subMenus = array();

	/**
	 * Constructor takes a name as its argument
	 *
	 * @param string $name
	 */
	public function __construct($name)
	{
		$this->menuName = $name;
	}

	/**
	 * Add an item to the current sub menu
	 *
	 * @param string $name
	 * @param string|Url $url
	 * @param string $label
	 * @return NavigationMenu
	 */
	public function addItem($name, $url, $label)
	{
		$item['url'] = $url;
		$item['label'] = $label;
		$this->subMenus[$this->activeSubMenu][$name] = $item;
		return $this;
	}

	/**
	 * Change the current active sub menu
	 *
	 * @param string $menuName
	 * @return NavigationMenu
	 */
	public function setMenu($menuName)
	{
		$this->activeSubMenu = $menuName;
		return $this;
	}

	/**
	 * Set the label for the current active sub menu
	 *
	 * @param string $label
	 * @return NavigationMenu
	 */
	public function setMenuLabel($label)
	{
		$this->subMenuLabels[$this->activeSubMenu] = $label;
		return $this;
	}

	/**
	 * Created an HTML menu to display
	 *
	 * @return string
	 */
	public function makeDisplay()
	{
		$userId = ActiveUser::getUser()->getId();
		$menuDiv = new HtmlObject('div');


		$menuContainsItem = false;

		$baseId = $this->menuName . '_sidebar_menu';
		$menuDiv->property('id', $baseId);
		$menuDiv->addClass('sidebar');
		foreach($this->subMenus as $menuName => $menuItems)
		{
			$wrapperDiv = new HtmlObject('div');
			$wrapperId = $baseId . '_' . $menuName;
			$wrapperDiv->property('id', $wrapperId);
			$wrapperDiv->addClass('sidebar_menu');
			if(isset($this->subMenuLabels[$menuName]))
			{
				$menuHeader = $wrapperDiv->insertNewHtmlObject('h2')->
											property('id', $wrapperId . '_label')->
											wrapAround($this->subMenuLabels[$menuName]);
			}

			$menuList = $wrapperDiv->insertNewHtmlObject('ul');
			$menuListId = $wrapperId . '_list';
			$menuList->property('id', $menuListId);
			$containsItem = false;
			foreach($menuItems as $name => $item)
			{
				if(isset($item['url']))
				{
					if($item['url'] instanceof Url)
					{
						if(!$item['url']->checkPermission($userId))
							continue;
						$item['url'] = (string) $item['url'];
					}


					$menuItem = $menuList->insertNewHtmlObject('li');

					$menuItem->property('id', $menuListId . '_' . $name)->
											addClass('sidebar_menu');

					$menuItem->insertNewHtmlObject('a')->
								property('href', (string) $item['url'])->
								wrapAround($item['label']);

					$containsItem = true;
				}
			}

			if(isset($menuItem))
			{
				$menuItem->addClass('last');
				unset($menuItem);
			}

			// if the menu contains something add it to the main menu div, otherwise discard
			if($containsItem === true)
			{
				$menuDiv->wrapAround($wrapperDiv);
				$menuContainsItem = true;
			}
		}

		return ($menuContainsItem === true) ? (string) $menuDiv : false;
	}
}

?>