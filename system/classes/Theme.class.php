<?php
/**
 * BentoBase
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
class Theme
{
	/**
	 * name of the current theme
	 *
	 * @var string
	 */
	public $name;

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
	 * Url for the active website
	 *
	 * @access protected
	 * @var string
	 */
	protected $url;

	/**
	 * Whether js and css minification is enabled
	 *
	 * @access protected
	 * @var bool
	 */
	protected $allowMin = true;

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

		$cache = new Cache('theme', $this->name, ActiveSite::getLink('theme'));
		$data = $cache->getData();

		if(!$cache->cacheReturned)
		{
			$baseModulePath = $config['path']['modules'];
			$baseModuleUrl = $config['url']['modules'];

			$baseModulePath = $config['path']['modules'];
			$baseModuleUrl = $config['url']['modules'];


			$packageList = new PackageList();
			$packages = $packageList->getInstalledPackages();

			$javascriptLinks = array();
			$cssLinks = array();

			foreach($packages as $package)
			{
				$packagePath = $baseModulePath . $package . '/';
				$packageUrl = $baseModuleUrl . $package . '/';

				// javascript
				$javascriptResult = $this->getFiles($packagePath . 'javascript/', $packageUrl . 'javascript/', 'js');
				if($javascriptResult)
					$javascriptLinks = array_merge_recursive($javascriptLinks, $javascriptResult);

				// css
				$cssResult = $this->getFiles($packagePath . 'css/', $packageUrl . 'css/', 'css');
				if($cssResult)
					$cssLinks = array_merge_recursive($cssLinks, $cssResult);

			}


			$themePath = $config['path']['theme'] . $name . '/';
			$themeUrl = $this->url;


			// javascript
			$javascriptResult = $this->getFiles($themePath . 'javascript/', $themeUrl . 'javascript/', 'js');
			if($javascriptResult)
				$javascriptLinks = array_merge_recursive($javascriptLinks, $javascriptResult);

			// css
			$cssResult = $this->getFiles($themePath . 'css/', $themeUrl . 'css/', 'css');
			if($cssResult)
				$cssLinks = array_merge_recursive($cssLinks, $cssResult);

			$bentoJavascriptPath = $config['path']['javascript'];
			$bentoJavascriptUrl = ActiveSite::getLink('javascript');

			// javascript
			// This code loads the javascript that ships with Bento- we load it last so it overrides any
			// javascript in the modules. Since we only store libraries here, and all modules use those libraries,
			// we don't want modules or themes to be able to overwrite those specific ones.
			$javascriptResult = $this->getFiles($bentoJavascriptPath, $bentoJavascriptUrl, 'js');
			if($javascriptResult)
				$javascriptLinks = array_merge_recursive($javascriptLinks, $javascriptResult);


/*
			$bentoCssPath = $config['path']['css'];
			$bentoCssUrl = $info->Site->getLink('css');

			// css
			$CssResult = $this->getFiles($bentoCssPath, $bentoCssUrl, 'js');
			if($CssResult)
				$cssLinks = array_merge_recursive($CssResult, $cssLinks);
				// the order is important- this method favors the Theme CSS files over the system ones
*/



			$data['cssLinks'] = $cssLinks;
			$data['jsLinks'] = $javascriptLinks;

			$cache->storeData($data);
		}

		$this->jsUrls = $data['jsLinks'];
		$this->cssUrls = $data['cssLinks'];
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
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * Returns all of the urls for the files in the requested directory
	 *
	 * @access protected
	 * @param string $path
	 * @param string $url This is the base url that the files are called from
	 * @param string $extention
	 * @return array
	 */
	protected function getFiles($path, $url, $extention = '.*')
	{

		if(strlen($path) < 1 || strlen($url) < 1)
			return false;

		$pattern = glob($path . '*' . $extention);
		$fileArray = array();
		foreach($pattern as $file)
		{
			$tmpArray = explode('/', $file);
			$fileName = array_pop($tmpArray);
			$fileDetails = explode('.', $fileName);
			$min = false;

			$extension = array_pop($fileDetails);

			$library = array_shift($fileDetails);

			switch (count($fileDetails))
			{
				case 0:
					$name = $library;
					$library = 'none';
					break;

				case 2:
					$option = array_pop($fileDetails);
					if($option = 'min')
					{
						$min = true;
					}

				case 1:
					$name = array_pop($fileDetails);
					break;

			}

			// name, library, extension, min

			if($min)
			{
				$fileArray[$library][$name]['minLink'] = $url . $fileName;
			}else{
				$fileArray[$library][$name]['mainLink'] = $url . $fileName;
			}

		}
		return $fileArray;
	}

	protected function loadUrl($type, $name, $library)
	{
		$filesAttribute = $type . 'Urls';

		if(isset($this->{$filesAttribute}[$library][$name]))
		{
			$output = ($this->allowMin && isset($this->{$filesAttribute}[$library][$name]['minLink'])) ?
															$this->{$filesAttribute}[$library][$name]['minLink'] :
															$this->{$filesAttribute}[$library][$name]['mainLink'];
			return $output;
		}else{
			return false;
		}
	}

}



?>