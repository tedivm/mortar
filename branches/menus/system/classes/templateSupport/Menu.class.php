<?php

class TagBoxMenu
{

	protected $menus;
	protected $menusUsed = array();
	protected $theme;

	public function __construct(MenuSystem $menus, Theme $theme)
	{
		$this->menus = $menus;
		$this->theme = $theme;

		$menuList = $this->menus->getMenuNames();
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
				if(is_numeric($key)) {
					$menu = array_slice($this->menus, 0, $key);
					$menusUsed[$menu->getName()] = true;
					break;
				}

				if(isset($this->menus[$key])) {
					$menu = $this->menus[$key];
					$menusUsed[$key] = true;
					break;
				}
		}

		if (isset($menu)) {
			$menuView = new ViewMenuDisplay($menu, $this->theme);
			return $menuView->getDisplay();
		} else {
			return false;
		}
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

}

?>