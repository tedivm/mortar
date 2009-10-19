<?php

class ViewThemeTemplate
{
	static $twigLoader = 'ViewTemplateTwigLoader';

	protected $theme;
	protected $name;
	protected $content = array();

	public function getDisplay()
	{
		try{
			$loader = $this->getTwigLoader();
			$twig = new Twig_Environment($loader);
			$template = $twig->loadTemplate($this->name);
			return $template->render($this->content);
		}catch(Exception $e){
			return false;
		}
	}

	protected function getTwigLoader()
	{
		$config = Config::getInstance();

		$basePaths = $this->getThemePaths();
		$basePaths['system'] = $config['path']['templates'];

		$cachePath = $config['path']['temp'] . '/twigCache';

		$loaderClass = staticHack(get_class($this), 'twigLoader');

		$loader = new $loaderClass($basePaths, $cachePath);
		return $loader;
	}

	protected function getThemePaths()
	{
		$paths = array();
		$theme = $this->theme;
		do
		{
			$paths[$theme->getName()] = $theme->getPath();
		}while($theme = $theme->getParentTheme());

		return $paths;
	}

}

class ViewTemplateTwigLoader extends Twig_Loader_Filesystem
{
	protected $lastLoadedList;
	protected $lastLoaded;
	protected $generationDelimiter = ':';

	public function load($name)
	{

		if($name == 'parent')
		{
			if(!isset($this->lastLoadedTemplates) || !isset($this->lastLoaded))
				throw new CoreError('Templates can not be named parent.');

			$found = false;
			$parentClass = false;
			foreach($this->lastLoadedList as $className => $path)
			{
				if($found)
				{
					$parentClass = $className;
					break;
				}

				if($className == $this->lastLoaded);
					$found = true;
			}

			if($parentClass)
			{
				$this->lastLoaded = $parentClass;

			}else{
				throw new CoreError('No parent class found for template.');
			}
		}else{

			if($generationDelimiterPosition = strpos($name, $this->generationDelimiter))
			{
				$generation = substr($name, 0, $generationDelimiterPosition);
				$name = substr($name, $generationDelimiterPosition);
			}

			$name = ltrim($name, $this->generationDelimiter);

			if($this->lastLoadedList = $this->loadTemplateSet($name))
				throw new CoreError('Unable to load template ' . $name);

			if(!isset($generation))
			{
				$themeNames = array_keys($this->lastLoadedList);
				$generation = $themeNames[0];
			}

			$className = $generation . $this->generationDelimiter . $name;
		}

		$this->lastLoaded = $className;
		return parent::load($className);
	}

	protected function loadTemplateSet($name)
	{
		$availableThemes = array_keys($this->paths);
		$activeTheme = $availableThemes[0];

		$cache = new Cache('templates', $activeTheme, $name);
		$templateSet = $cache->getData();

		if($cache->isStale())
		{
			$templateSet = array();
			foreach($this->paths as $generation => $basepath)
			{
				$path = realpath($basepath . $name);

				if(0 !== strpos($file, $path))
					throw new CoreSecurity('Template ' . $name . ' is attempting to load files outside its directory.');

				if(!file_exists($path))
					continue;

				$classname = $generation . ':' . $name;
				$templateSet[$classname] = $path;
			}

			$cache->storeData($templateSet);
		}

		return $templateSet;
	}

  /**
   * Gets the source code of a template, given its name.
   *
   * @param  string $name string The name of the template to load
   *
   * @return array An array consisting of the source code as the first element,
   *               and the last modification time as the second one
   *               or false if it's not relevant
   */
  public function getSource($name)
  {
	if(isset($this->lastLoaded[$name]))
	{
		return array(file_get_contents($this->lastLoaded[$name]), filemtime($this->lastLoaded[$name]));
	}else{
		new CoreInfo('Unable to load template ' . $name);
	}
}

?>