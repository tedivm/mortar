<?php

class NavigationMenu
{

	protected $menuName;
	protected $subMenuLabels = array();
	protected $activeSubMenu;
	protected $subMenus;

	public function __construct($name)
	{
		$this->menuName = $name;
	}

	public function addItem($name, $url, $label)
	{
		$item['url'] = $url;
		$item['label'] = $label;
		$this->subMenus[$this->activeSubMenu][$name] = $item;
		return $this;
	}

	public function setMenu($menuName)
	{
		$this->activeSubMenu = $menuName;
		return $this;
	}

	public function setMenuLabel($label)
	{
		$this->subMenuLabels[$this->activeSubMenu] = $label;
		return $this;
	}

	public function makeDisplay()
	{
		$userId = ActiveUser::getInstance()->getId();
		$menuDiv = new HtmlObject('div');


		$menuContainsItem = false;

		$baseId = $this->menuName . '_sidebar_menu';
		$menuDiv->property('id', $baseId);
		$menuDiv->addClass('sidebar');
		foreach($this->subMenus as $menuName => $menuItems)
		{
			$wrapperDiv = new HtmlObject('div');
			$wrapperId = $baseId . $menuName;
			$wrapperDiv->property('id', $wrapperId);
			$wrapperDiv->addClass('sidebar_menu');
			if(isset($this->subMenuLabels[$menuName]))
			{
				$menuHeader = $wrapperDiv->insertNewHtmlObject('h2')->
											property('id', $wrapperId . '_label')->
											wrapAround($this->subMenuLabels[$menuName]);
			}

			$menuList = $wrapperDiv->insertNewHtmlObject('ul');
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

					$menuItem->property('id', $wrapperId . '_' . $name)->
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

/*
				<div id="BB_main_right" class="BB_sidebar">

					<div class="BB_sidebar_menu" id="BB_sidebar_menu_5">
						<h2>Wiki Manager Options</h2>
						<ul>
							<li id="BB_menu_item_1" class="BB_menu_item">Option</li>
							<li id="BB_menu_item_2" class="BB_menu_item">Option</li>
							<li id="BB_menu_item_3" class="BB_menu_item">Flappy Apples</li>

							<li id="BB_menu_item_4" class="BB_menu_item">Ineffectiual Option</li>
						</ul>
					</div> <!-- BB_sidebar_menu_1 -->

				</div>
*/
?>