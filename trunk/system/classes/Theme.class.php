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
	 * Directory of the current active theme.
	 *
	 * @var string
	 */
	protected $pathToTheme;

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

		if($cache->isStale())
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
			$this->pathToTheme = $themePath;
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
	 * This function returns the requested template for a model type. This first checks the theme/models/ModelType
	 * (where model type is $model) directory first, and then falls back to calling the getTemplateFromPackage method.
	 * When falling back to the package, the template name gets the model name prepended to it (so when looking for the
	 * Listing template for a Page model, PageListing would be checked in the package).
	 *
	 * @param string $template
	 * @param string $model
	 * @return string|false Returns a string or, if no suitable template can be found, false.
	 */
	public function getModelTemplate($template, $model)
	{
		if($templateString = $this->getTemplateFromTheme('models', $model, $template))
			return $templateString;

		$handler = ModelRegistry::getHandler($model);

		if($templateString = $this->getTemplateFromPackage($model . $template, $handler['module']))
			return $templateString;

		if($templateString = $this->getTemplateFromSystem('models', $template))
			return $templateString;

		return false;
	}

	/**
	 * This function returns the requested template for a package. This first checks the theme/packages/PackageName
	 * (where PackageName is the name of the package) then, if no template is present, checks to see if a template
	 * was shipped with the package.
	 *
	 * @param string $template
	 * @param string $package
	 * @return string|false Returns a string or, if no suitable template can be found, false.
	 */
	public function getTemplateFromPackage($template, $package)
	{
		$packageInfo = new PackageInfo($package);
		$package = $packageInfo->getName();


		if($templateString = $this->getTemplateFromTheme('packages', $package, $template))
			return $templateString;

		if(filter_var($template, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/[^0-9a-zA-Z\.]/i'))) !== false)
			return false;

		$path = $packageInfo->getPath() . 'templates/' . $template;

		if(!file_exists($path) || !is_readable($path))
			return false;

		$templateString = file_get_contents($path);

		if($templateString === false)
			return false;

		return $templateString;
	}

	/**
	 * This function takes in a variable number of arguments, with the first arguments being containers and the last
	 * argument passed being the actual name of the template. For example, $theme->getTemplateFromTheme('models',
	 * 'Page', 'Listing.html') will check for the the file "theme/models/Page/Listing.html" while
	 * $theme->getTemplateFromTheme('index.html') will check for "theme/index.html".
	 *
	 * @param string|null $container,..
	 * @param string $templatename
	 * @return string|false Returns a string or, if no suitable template can be found, false.
	 */
	public function getTemplateFromTheme()
	{
		if(func_num_args() < 1)
			return false;

		$args = func_get_args();
		$templateName = array_pop($args);
		$path = $this->pathToTheme;

		return $this->getTemplateFromContainer($path, $templateName, $args);
	}

	/**
	 * This function loads templates from the base system.
	 *
	 * @return string|false Returns false if the template can't be found.
	 */
	public function getTemplateFromSystem()
	{
		if(func_num_args() < 1)
			return false;

		$args = func_get_args();
		$templateName = array_pop($args);
		$config = Config::getInstance();
		$path = $config['path']['templates'];
		return $this->getTemplateFromContainer($path, $templateName, $args);
	}



	/**
	 * This function takes in a base path, template name and container (which is an array of strings) and returns the
	 * specified template, or false if it is not found. The container array represents a hierarchal structure, which
	 * each element representing a deeper folder in the system.
	 *
	 * @param string $path
	 * @param string $templateName
	 * @param array $container
	 * @return string|false Returns false if it can not find the template.
	 */
	protected function getTemplateFromContainer($path, $templateName, $container = array())
	{
		$regexp = array('options' => array('regexp' => '/[^0-9a-zA-Z\.]/i'));

		if(filter_var($templateName, FILTER_VALIDATE_REGEXP, $regexp) !== false)
			return false;

		$regexp = array('options' => array('regexp' => '/[^0-9a-zA-Z]/i'));

		foreach($container as $pathPiece)
		{
			if(filter_var($pathPiece, FILTER_VALIDATE_REGEXP, $regexp) !== false)
				return false;


			$path .= $pathPiece . '/';
		}
		$path .= $templateName;

		if(!file_exists($path) || !is_readable($path))
			return false;

		$templateString = file_get_contents($path);

		if($templateString === false)
			return false;

		return $templateString;
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