<?php

class TagBoxFonts
{

	protected $fontList = array();
	protected $decs = array();
	protected $theme;

	public function __construct($theme = null)
	{
		$config = Config::getInstance();
		$fontPath = $config['path']['fonts'];
		$installedFonts = glob($fontPath . "*", GLOB_ONLYDIR);
		foreach($installedFonts as $fontPath) {
			$fontName = array_reverse(explode('/', $fontPath));
			$this->fontList[] = $fontName[0];
		}

		if(isset($theme)) {
			$this->theme = $theme;
		}
	}

	protected function getAtDeclarations($useTheme = false)
	{
		if($useTheme && !isset($this->theme))
			return false;

		if($useTheme) {
			$cache = CacheControl::getCache('fonts', 'tagbox', 'theme', $this->theme->getName());
		} else {
			$cache = CacheControl::getCache('fonts', 'tagbox', 'all');
		}
		$data = $cache->getData();

		if($cache->isStale())
		{
			if($useTheme) {
				$settings = $this->theme->getSettings();
				if(!isset($settings['fonts']['all'])) {
					if(isset($settings['fonts']['font'])) {
						$fontlist = $settings['fonts']['font'];
						$this->fontList = array_intersect($fontlist, $this->fontList);
					} else {
						$this->fontList = array();
					}
				}
			}

			foreach($this->fontList as $fontName)
			{
				$font = new Font($fontName);
				$data[$fontName] = $font->getCss();
			}

			$cache->storeData($data);
		}

		$decs = '';

		if(is_array($data)) foreach ($data as $css)
			$decs .= $css;

		return $decs;

	}

	public function __get($tagname)
	{
		switch($tagname) {
			case 'all':
				return $this->getAtDeclarations();
			case 'theme':
				return $this->getAtDeclarations(true);
			default:
				return false;
		}
	}

	public function __isset($tagname)
	{
		switch($tagname) {
			case 'all':
			case 'theme':
				return true;
			default:
				return false;
		}
	}
}

?>
