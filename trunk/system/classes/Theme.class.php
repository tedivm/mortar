<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Display
 */

/**
 * This class provides information, including a lot of urls, about the current theme
 *
 * Themes work by overwritting defaults that are set in modules or by the system. This way themes can get as customized
 * as the designer wants, or as close to the original. The other benefit is that themes don't need to be all
 * encompassing, since the defaults will get installed with new modules, making the modules fit right into the system
 *
 * @package System
 * @subpackage Display
 */
class Theme extends ContentBase
{

	/**
	 * Urls for all of the CSS files
	 *
	 * @access protected
	 * @var array
	 */
	protected $cssUrls;

	/**
	 * Urls for all of the javascript files
	 *
	 * @access protected
	 * @var array
	 */
	protected $jsUrls;

	/**
	 * Whether js and css minification is enabled
	 *
	 * @access protected
	 * @var bool
	 */
	protected $allowMin = true;

	/**
	 * The iconset currently in use by this theme
	 *
	 * @access protected
	 * @var Iconset
	 */
	protected $iconset;

	protected $contentType = 'theme';
	protected $imagePath = 'images/';

	/**
	 * Constructor takes the name of the theme and loads the initial information
	 *
	 * @cache theme *name *link
	 * @param string $name
	 */
	public function __construct($name)
	{
		$config = Config::getInstance();

		if(defined('DEBUG') && DEBUG > 1)
			$this->allowMin = false;

		$this->name = $name;
		$this->url = ActiveSite::getLink('theme') . $name . '/';
		$themePath = $config['path']['theme'] . $name . '/';
		$this->contentPath = $themePath;
		$themeUrl = $this->url;

		$cache = new Cache('theme', $this->name, ActiveSite::getLink('theme'));
		$data = $cache->getData();

		if($cache->isStale())
		{
			$settingsPath = $this->contentPath . 'settings.ini';

			if(is_readable($settingsPath))
			{
				$iniFile = new IniFile($settingsPath);
				$data['settings'] = $iniFile->getArray();
			}

			if(isset($data['settings']['meta']['extends']))
			{
				$parentTheme = new Theme($data['settings']['meta']['extends']);
				$cssLinks = $parentTheme->getCssFiles();
				$javascriptLinks = $parentTheme->getJsFiles();

				$parentSettings = $parentTheme->getSettings();

				// the meta settings are things like author, name and license and shouldn't inherit
				if(isset($parentSettings['meta'])) unset($parentSettings['meta']);

				$data['settings'] = array_merge_recursive($parentSettings, $data['settings']);
			}else{
				$javascriptLinks = array();
				$cssLinks = array();
			}

			$baseModulePath = $config['path']['modules'];
			$baseModuleUrl = $config['url']['modules'];

			$baseModulePath = $config['path']['modules'];
			$baseModuleUrl = $config['url']['modules'];


			$packageList = new PackageList();
			$packages = $packageList->getInstalledPackages();

			foreach($packages as $package)
			{
				$packagePath = $baseModulePath . $package . '/';
				$packageUrl = $baseModuleUrl . $package . '/';

				// javascript
				$javascriptResult = $this->getFiles($packagePath . 'javascript/', $packageUrl . 'javascript/', 'js', 25);
				if($javascriptResult)
					$javascriptLinks = array_merge_recursive($javascriptLinks, $javascriptResult);

				// css
				$cssResult = $this->getFiles($packagePath . 'css/', $packageUrl . 'css/', 'css');
				if($cssResult)
					$cssLinks = array_merge_recursive($cssLinks, $cssResult);

			}

			// javascript
			$javascriptResult = $this->getFiles($themePath . 'javascript/', $themeUrl . 'javascript/', 'js');
			if($javascriptResult)
				$javascriptLinks = array_merge_recursive($javascriptLinks, $javascriptResult);

			// css
			$cssResult = $this->getFiles($themePath . 'css/', $themeUrl . 'css/', 'css');
			if($cssResult)
				$cssLinks = array_merge_recursive($cssLinks, $cssResult);



			$baseJavascriptPath = $config['path']['javascript'];
			$baseJavascriptUrl = ActiveSite::getLink('javascript');

			// javascript
			// This code loads the javascript that ships with Mortar- we load it last so it overrides any
			// javascript in the modules. Since we only store libraries here, and all modules use those libraries,
			// we don't want modules or themes to be able to overwrite those specific ones.
			$javascriptResult = $this->getFiles($baseJavascriptPath, $baseJavascriptUrl, 'js', 20);
			if($javascriptResult)
				$javascriptLinks = array_merge_recursive($javascriptLinks, $javascriptResult);

			$data['cssLinks'] = $cssLinks;
			$data['jsLinks'] = $javascriptLinks;

			$cache->storeData($data);
		}

		if(isset($data['settings']))
			$this->settings = $data['settings'];

		if(isset($this->settings['images']['iconset']))
			$this->iconset = new Iconset($this->settings['images']['iconset']);

		$this->jsUrls = $data['jsLinks'];
		$this->cssUrls = $data['cssLinks'];
	}

	/**
	 * Returns the parent theme or false if there isn't one.
	 *
	 * @return Theme|false
	 */
	public function getParentTheme()
	{
		if(!isset($this->settings['meta']['extends']))
			return false;

		return new Theme($this->settings['meta']['extends']);
	}

	/**
	 * Returns the current iconset or false if there isn't one.
	 *
	 * @return Iconset|false
	 */
	public function getIconset()
	{
		if(isset($this->iconset))
			return $this->iconset;
		else
			return false;
	}

	public function getPaths($type = 'css')
	{
		if($type == 'css')
		{
			$urlArray = $this->cssUrls;
		}elseif($type == 'js'){
			$urlArray = $this->jsUrls;
		}else{
			return false;
		}

		$paths = array();
		foreach($urlArray as $section)
		{
			foreach($section as $url)
			{
				$priority = isset($url['priority']) ? (int) $url['priority'] : 30;

				if($priority == 0)
					continue;

				if(isset($url['path']))
					$paths[$priority][] = $url['path'];
			}
		}
		ksort($paths);
		$finalPaths = call_user_func_array('array_merge', $paths);
		return $finalPaths;
	}

	public function getCssFiles()
	{
		return isset($this->cssUrls) ? $this->cssUrls : array();
	}

	public function getJsFiles()
	{
		return isset($this->jsUrl) ? $this->jsUrl : array();
	}

	/**
	 * Returns the matching url
	 *
	 * @param string $name
	 * @param string $library
	 * @return string
	 */
	public function jsUrl($name, $library = 'none')
	{
		return $this->loadUrl('js', $name, $library);
	}

	/**
	 * Returns the matching url
	 *
	 * @param string $name
	 * @param string $library
	 * @return string
	 */
	public function cssUrl($name, $library = 'none')
	{
		return $this->loadUrl('css', $name, $library);
	}

	/**
	 * Returns the base url for the theme
	 *
	 * @return string
	 */
	public function getUrl($type = null)
	{
		if(!isset($type))
			return $this->url;

		if($type == 'js' || $type == 'css')
		{
			$cache = new Cache($this->contentType, $this->name, 'minification', $type, 'url');
			$url = $cache->getData();

			if($cache->isStale())
			{
				$minifier = $this->getMinifier($type);
				$initialCheckSum = $minifier->getInitialChecksum();
				$url = new Url();
				$url->module = 'Mortar';
				$url->action = 'Minify';
				$url->id = $this->name . '-' . $initialCheckSum . '.' . $type;
				$cache->storeData($url);
			}
			return $url;

		}else{
			return false;
		}

	}

	public function getMinifier($type = 'js')
	{
		$type = ($type == 'js') ? 'js' : 'css';
		$minifier = new Minifier($type);
		$minifier->addFiles($this->getPaths($type));

		if($type !== 'js')
		{
			$baseString = "{{ fonts.all }}\n" . $minifier->getBaseString();
			$fileTemplate = new ViewStringTemplate($baseString);

			$themeBox = new TagBoxTheme($this);
			$fontBox = new TagBoxFonts();

			$fileTemplate->addContent(array('theme' => $themeBox, 'fonts' => $fontBox));

			$baseString = $fileTemplate->getDisplay();
			$minifier->setBaseString($baseString);
		}

		return $minifier;
	}


	/**
	 * This function attempts to return the URL for the requested resource. If minification is enabled, and a minified
	 * version is available, that URL is returned instead.
	 *
	 * @param string $type js or css
	 * @param string $name
	 * @param string $library
	 * @return string|false Returns false if the requested URL does not exist.
	 */
	protected function loadUrl($type, $name, $library)
	{
		$filesAttribute = $type . 'Urls';

		if(isset($this->{$filesAttribute}[$library][$name]))
		{
			return $this->{$filesAttribute}[$library][$name]['mainLink'];
		}else{
			return false;
		}
	}
}

class ThemeImageWrapper implements ArrayAccess
{
	protected $theme;

	public function __construct(Theme $theme)
	{
		$this->theme = $theme;
	}

	public function offsetGet($offset)
	{
		return $this->theme->getImageUrl($offset);
	}

	public function offsetExists($offset)
	{
		return (bool) $this->theme->getImageUrl($offset);
	}

	public function offsetUnset($offset)
	{

	}

	public function offsetSet($offset, $value)
	{

	}

}


?>