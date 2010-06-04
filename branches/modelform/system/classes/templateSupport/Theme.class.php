<?php

class TagBoxTheme
{

	protected $theme;
	protected $images;
	protected $icons;
	protected $settings;
	
	public function __construct(Theme $theme)
	{
		$this->theme = $theme;
		$iconset = $theme->getIconset();
		$this->images = new ThemeImageWrapper($theme);
		$this->icons = new IconsetImageWrapper($iconset);
	}
	
	public function __get($key)
	{
		switch($key) {
			case 'icons':
				return $this->icons;
			case 'images':
				return $this->images;
			case 'settings':
				return $this->theme->getSettings();
			case 'name':
				return $this->theme->getName();
			case 'path':
				return $this->theme->getThemeUrl();
			default:
				return false;
		}
	}

	public function __isset($key)
	{
		switch($key) {
			case 'icons':
			case 'images':
			case 'settings':
			case 'name':
			case 'path':
				return true;
			default:
				return false;
		}	
	}
}

?>
