<?php

class Iconset extends ContentBase
{
	static protected $iconsets = array();
	protected $contentType = 'iconset';
	protected $path = array('icon'  => '');
	protected $theme;

	static public function loadIconset($name, $theme)
	{
		if(isset(self::$iconsets[$name])) {
			return self::$iconsets[$name];
		}

		$iconset = new Iconset($name, $theme);
		self::$iconsets[$name] = $iconset;

		return $iconset;
	}

	/**
	 * Constructor takes the name of the iconset and loads the initial information
	 *
	 * @cache iconset *name *link
	 * @param string $name
	 * @param Theme $theme
	 */
	public function __construct($name, $theme)
	{
		$config = Config::getInstance();

		$this->theme = $theme;

		$this->name = $name;
		$this->url = ActiveSite::getLink('icons') . $name . '/';
		$iconsPath = $config['path']['icons'] . $name . '/';
		$this->contentPath = $iconsPath;
		$iconsUrl = $this->url;

		$cache = CacheControl::getCache('iconset', $this->name, 'ini');
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
		$cache = CacheControl::getCache('iconset', $this->name, 'loadIcon', $this->theme->getName(), $name);

		$data = $cache->getData();

		if($cache->isStale()) {
			do {
				$theme = $this->theme;
				$themeSettings = $theme->getSettings();
				if(isset($themeSettings['icons'][$name])) {
					$data = $theme->getImageUrl($themeSettings['icons'][$name], 'icon');
					break;
				}

				if(isset($this->settings['icons'][$name])) {
					$data = $this->getImageUrl($this->settings['icons'][$name], 'icon');
					break;
				}

				$packagelist = new PackageList();
				$list = $packagelist->getInstalledPackages();
				$pieces = explode('_', $name);

				if(count($pieces) >= 3) {
					if(isset($list[$pieces[0]][$pieces[1]])) {
						$moduleName = $pieces[1];
						$moduleFamily = $pieces[0];
						$pathPiece = $moduleFamily . '/' . $moduleName;
					}
				}

				if(!isset($pathPiece) && !isset($list['orphan'][$pieces[0]])) {
					$data = false;
					break;
				} else {
					$moduleName = $pieces[0];
					$moduleFamily = 'orphan';
					$pathPiece = $moduleName;
				}

				$config = Config::getInstance();
				$baseModulePath = $config['path']['modules'];
				$settingsPath = $baseModulePath . $pathPiece . '/icons/settings.ini';

				if(!is_readable($settingsPath)) {
					$data = false;
					break;
				}

				$iniFile = new IniFile($settingsPath);
				$moduleSettings = $iniFile->getArray();

				if(isset($moduleSettings['icons'][$name])) {
					$imageName = $moduleSettings['icons'][$name];
					$info = PackageInfo::loadByName($moduleFamily, $moduleName);
					$path = $info->getPath();

					$imagePath = $path . 'icons/' . $imageName;
					if($realPath = realpath($imagePath)) {
						if(!strpos($realPath, $path . 'icons/') === 0) {
							throw new CoreSecurity('Attempted to load image outside the icon directory.');
						}

						$data = ActiveSite::getLink('modules') . $pieces[0] . '/icons/' . $imageName;
						break;
					}
				}

				$data = false;
				break;
			} while(1);

			$cache->storeData($data);
		}

		return $data;
	}

	public function hasIcon($name)
	{
		return (bool) $this->loadIcon($name);
	}

	/**
	 * Returns an HTML representation of the requested icon, either as an image or span-wrapped
	 * text. Accepts an optional $classes parameter to insert a set of classes into the
	 * generated HTML, and an optional alt parameter to provide the text returned if no icon
	 * is found.
	 *
	 * @param string $name
	 * @param string|null $classes
	 * @param string|null $alt
	 * @return string
	 */
	public function getIcon($name, $classes = null, $alt = null)
	{
		if (isset($classes))
			$classPhrase = "class='$classes'";
		else
			$classPhrase = '';

		if(!isset($alt))
			$alt = $name;

		if ($iconUrl = $this->loadIcon($name)) {
			$icon = "<img src='$iconUrl' $classPhrase alt='$alt' title='$name' />";
		} else {
			$icon = "<span $classPhrase >$alt</span>";
		}

		return $icon;
	}

}


class IconsetImageWrapper implements ArrayAccess
{
	protected $iconset;

	public function __construct(Iconset $iconset)
	{
		$this->iconset = $iconset;
	}

	public function offsetGet($offset)
	{
		return $this->iconset->getIcon($offset);
	}

	public function offsetExists($offset)
	{
		return $this->iconset->hasIcon($offset);
	}

	public function offsetUnset($offset)
	{

	}

	public function offsetSet($offset, $value)
	{

	}

}

?>
