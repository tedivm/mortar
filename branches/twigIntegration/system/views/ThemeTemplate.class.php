<?php

class ViewThemeTemplate
{
	protected $twigLoader = 'ViewTemplateTwigLoader';
	protected $theme;
	protected $name;

	protected $content = array();

	public function __construct(Theme $theme, $name)
	{
		$this->theme = $theme;
		$this->name = $name;
	}

	public function addContent($array)
	{
		$this->content = array_merge($this->content, $array);
	}

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


		$loaderClass = $this->twigLoader;

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
			throw new CoreError('Templates can not be named parent.');
		}

		if($generationDelimiterPosition = strpos($name, $this->generationDelimiter))
		{
			$generation = substr($name, 0, $generationDelimiterPosition);
			$name = substr($name, $generationDelimiterPosition);
		}

		$name = ltrim($name, $this->generationDelimiter);

		if(!($this->lastLoadedList = $this->loadTemplateSet($name)))
			throw new CoreError('Unable to load template ' . $name);

		// if no generation is set assume its the highest level available
		if(!isset($generation))
		{
			$templates = array_keys($this->lastLoadedList);
			$className = array_shift($templates);
		}else{
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
				$path = realpath($basepath . '/' . $name);

				if($path === false)
					continue;

				if(0 !== strpos($path, $basepath))
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
		if(isset($this->lastLoadedList[$name]))
		{
			$fileContents = file_get_contents($this->lastLoadedList[$name]);

			$found = false;
			foreach($this->lastLoadedList as $className => $path)
			{
				if($found && $className != $name)
				{
					$fileContents =
						str_replace('{% extends "parent" %}', '{% extends "' . $className . '" %}', $fileContents);


					break;
				}

				if($className == $name);
					$found = true;

			}

			return array($fileContents, filemtime($this->lastLoadedList[$name]));
		}else{
			throw new CoreInfo('Unable to load template ' . $name);
		}
	}


  /**
   * Sets the paths where templates are stored.
   *
   * @param string|array $paths A path or an array of paths where to look for templates
   */
  public function setPaths($paths)
  {
		if(!is_array($paths))
		{
			$paths = array($paths);
		}

		$this->paths = array();
		foreach ($paths as $label => $path)
		{
			$this->paths[$label] = realpath($path);
		}
	}

}

?>