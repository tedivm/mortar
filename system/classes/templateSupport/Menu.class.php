<?php

class TagBoxMenu
{

	protected $menus;
	protected $menusUsed = array();
	protected $theme;

	public function __construct(MenuSystem $menus, Theme $theme)
	{
		$this->menus = $menus->getMenus();
		$this->theme = $theme;

		$menuList = $menus->getMenuNames();
		foreach($menuList as $name)
			$this->menusUsed[$name] = false;
	}

	public function __get($key)
	{
		switch($key) {
			case 'next':
				return $this->getNext();
			case 'remaining':
				return $this->getRemaining();
			default:
				return $this->show($key);
		}
		return false;
	}

	public function __isset($key)
	{
		switch($key) {
			case 'next':
			case 'remaining':
				return true;
			default:
				if(is_numeric($key) || isset($this->menus[$key]))
					return true;
				
				return false;
		}
	}

	protected function getNext()
	{
		foreach($this->menusUsed as $menu => $isUsed) {
			if(!$isUsed) {
				$this->menusUsed[$menu] = true;
				$menuView = new ViewMenuDisplay($this->menus[$menu], $this->theme);
				return $menuView->getDisplay();
			}
		}
		
		return false;
	}

	protected function getRemaining()
	{
		$menuContent = '';

		foreach($this->menusUsed as $menu => $isUsed) {
			if(!$isUsed) {
				$this->menusUsed[$menu] = true;
				$menuView = new ViewMenuDisplay($this->menus[$menu], $this->theme);
				$menuContent .= $menuView->getDisplay();
			}	
		}

		return ($menuContent === '') ? false : $menuContent;
	}

	public function show($key, $template = '')
	{
		if(is_numeric($key)) {
			$menu = array_slice($this->menus, 0, $key);
			$this->menusUsed[$menu->getName()] = true;
		} elseif(isset($this->menus[$key])) {
			$menu = $this->menus[$key];
			$this->menusUsed[$key] = true;
		}

		if(isset($menu)) {
			$menuView = new ViewMenuDisplay($menu, $this->theme);
			$menuView->useItemTemplate('support/MenuItem' . $template . '.html');
			$menuView->useMenuTemplate('support/Menu' . $template . '.html');
			return $menuView->getDisplay();
		} else {
			return false;
		}
	}
}

?>