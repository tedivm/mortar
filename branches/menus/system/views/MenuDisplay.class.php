<?php

class ViewMenuDisplay
{

	protected $menu;

	public function __construct(Menu $menu, Theme $theme)
	{
		$this->menu = $menu;
		$this->theme = $theme;
	}

	public function getDisplay()
	{
		$menuItems = $this->menu->getItems();

		$menuContent = '';

		foreach($menuItems as $item) {
			if ($item['isMenu']) {
				$itemView = new ViewMenuDisplay($item['item'], $this->theme);
			} else {
				$itemView = new ViewThemeTemplate($this->theme, 'MenuItem.html');
				$itemView->addContent(array('name' => $item['name'], 'item' => $item['item']));
			}
			
			$menuContent .= $itemView->getDisplay();
		}

		$menuView = new ViewThemeTemplate($this->theme, 'Menu.html');
		$menuView->addContent(array('content' => $menuContent));

		return $menuView->getDisplay();
	}
}

?>