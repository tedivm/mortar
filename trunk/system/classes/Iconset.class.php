<?php

class Iconset extends ContentBase
{
	protected $contentType = 'iconset';
	protected $path = array('icon'  => '');


	/**
	 * Constructor takes the name of the iconset and loads the initial information
	 *
	 * @cache iconset *name *link
	 * @param string $name
	 */
	public function __construct($name)
	{
		$config = Config::getInstance();

		$this->name = $name;
		$this->url = ActiveSite::getLink('icons') . $name . '/';
		$iconsPath = $config['path']['icons'] . $name . '/';
		$this->contentPath = $iconsPath;
		$iconsUrl = $this->url;

		$cache = CacheControl::getCache('iconset', $this->name, ActiveSite::getLink('icons'));
		$data = $cache->getData();

		if($cache->isStale())
		{
			$settingsPath = $this->contentPath . 'settings.ini';

			if(is_readable($settingsPath))
			{
				$iniFile = new IniFile($settingsPath);
				$data['settings'] = $iniFile->getArray();
			}

			$cache->storeData($data);
		}

		if(isset($data['settings']))
			$this->settings = $data['settings'];
	}

	/**
	 * Uses the name of an icon to attempt to load it from several locations in a predetermined order:
	 * first from the current theme, then from the current iconset, and finally, if the icon's name
	 * begins with a module name, from that module.
	 *
	 * @param string $name
	 * @return string
	 */
	protected function loadIcon($name)
	{
		$page = ActivePage::getInstance();
		$theme = $page->getTheme();
		$themeSettings = $theme->getSettings();
		if(isset($themeSettings['icons'][$name]))
			return $theme->getImageUrl($themeSettings['icons'][$name], 'icon');

		if(isset($this->settings['icons'][$name]))
			return $this->getImageUrl($this->settings['icons'][$name], 'icon');

		$packagelist = new PackageList();
		$list = $packagelist->getInstalledPackages();
		$pieces = explode('_', $name);

		if(!isset($pieces[0]) || !in_array($pieces[0], $list))
			return false;

		$config = Config::getInstance();
		$baseModulePath = $config['path']['modules'];
		$settingsPath = $baseModulePath . $pieces[0] . '/icons/settings.ini';

		if(!is_readable($settingsPath))
			return false;
		
		$iniFile = new IniFile($settingsPath);
		$moduleSettings = $iniFile->getArray();

		if(isset($moduleSettings['icons'][$name])) {
			$imageName = $moduleSettings['icons'][$name];
			$info = new PackageInfo($pieces[0]);
			$path = $info->getPath();
	
			$imagePath = $path . 'icons/' . $imageName;
			if($realPath = realpath($imagePath)) {
				if(!strpos($realPath, $path . 'icons/') === 0)
					throw new CoreSecurity('Attempted to load image outside the icon directory.');

				$url = ActiveSite::getLink('modules') . $pieces[0] . '/icons/' . $imageName;
				return $url;
			}
		}

		return false;
	}

	/**
	 * Returns an HTML representation of the requested icon, either as an image or span-wrapped
	 * text. Accepts an optional $classes parameter to insert a set of classes into the
	 * generated HTML.
	 *
	 * @param string $name
	 * @param string|null $classes
	 * @return string
	 */
	public function getIcon($name, $classes = null)
	{
		if (isset($classes))
			$classPhrase = "class='$classes'";
		else
			$classPhrase = '';

		if ($iconUrl = $this->loadIcon($name)) {
			$icon = "<img src='$iconUrl' $classPhrase alt='$name' title='$name' />";
		} else {
			$icon = "<span $classPhrase >$name</span>";
		}

		return $icon;
	}

}

?>
