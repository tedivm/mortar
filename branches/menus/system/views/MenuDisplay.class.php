<?php

class ViewMenuDisplay
{
	protected $menu;
	protected $theme;
	protected $level;

	public function __construct(Menu $menu, Theme $theme, $level = 1)
	{
		$this->menu = $menu;
		$this->theme = $theme;
		$this->level = $level;
	}

	public function getDisplay()
	{
		$menuItems = $this->menu->getItems();

		$menuContent = '';

		foreach($menuItems as $item) {
			if ($item['item'] instanceof Menu) {
				$itemView = new ViewMenuDisplay($item['item'], $this->theme, $this->level + 1);
			} else {
				$itemView = new ViewThemeTemplate($this->theme, 'support/MenuItem.html');
				$itemView->addContent(array('name' => $item['name'], 'item' => $item['item']));
			}
			
			$menuContent .= $itemView->getDisplay();
		}

		$menuView = new ViewThemeTemplate($this->theme, 'support/Menu.html');
		$menuView->addContent(array('name' => $this->menu->getName(), 'content' => $menuContent, 'level' => $this->level));

		return $menuView->getDisplay();
	}
}

?>