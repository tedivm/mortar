<?php

class ViewMenuDisplay
{
	protected $menu;
	protected $theme;
	protected $level;
	protected $itemTemplate = 'support/MenuItem.html';
	protected $menuTemplate = 'support/Menu.html';
	protected $content = array();

	public function __construct(Menu $menu, Theme $theme, $level = 1)
	{
		$this->menu = $menu;
		$this->theme = $theme;
		$this->level = $level;
	}

	public function useItemTemplate($template)
	{
		$this->itemTemplate = $template;
	}

	public function useMenuTemplate($template)
	{
		$this->menuTemplate = $template;
	}

	public function addContent($content)
	{
		if(is_array($content))
			$this->content = $content;
	}

	public function getDisplay()
	{
		$menuItems = $this->menu->getItems();

		if (count($menuItems) === 0)
			return '';

		$menuContent = '';

		$x = 1;

		foreach($menuItems as $item) {
			if ($item['item'] instanceof Menu) {
				$itemView = new ViewMenuDisplay($item['item'], $this->theme, $this->level + 1);
				$itemView->useItemTemplate($this->itemTemplate);
				$itemView->useMenuTemplate($this->menuTemplate);
			} else {
				$itemView = new ViewThemeTemplate($this->theme, $this->itemTemplate);
				$itemView->addContent(array('name' => $item['name'], 'item' => $item['item']));
			}

			$content = array();
			if($x === 1)
				$content['first'] = 'first';
			if($x === count($menuItems))
				$content['last'] = 'last';
			$content['number'] = $x++;

			$itemView->addContent($content);
			$itemView->addContent($this->content);
			$menuContent .= $itemView->getDisplay();
		}

		$menuView = new ViewThemeTemplate($this->theme, $this->menuTemplate);
		$menuView->addContent(array('name' => $this->menu->getName(), 'content' => $menuContent, 'level' => $this->level));

		return $menuView->getDisplay();
	}
}

?>