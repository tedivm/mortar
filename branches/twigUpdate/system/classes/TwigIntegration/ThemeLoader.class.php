<?php

class TwigIntegrationThemeLoader implements Twig_LoaderInterface
{
	protected $lastLoadedList;
	protected $lastLoaded;
	protected $generationDelimiter = ':';
	protected $paths;

	public function loadTemplateSet($name)
	{
		$availableThemes = array_keys($this->paths);

		$cache = new Cache('themes', $availableThemes[0], 'templates', $name);
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
			$extras = $this->loadExtraClasses($name);
			$templateSet = array_merge($templateSet, $extras);

			$cache->storeData($templateSet);
		}

		$this->lastLoadedList = $templateSet;
		return $templateSet;
	}

	protected function loadExtraClasses($name)
	{
		return array();
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
			// We're iterating through the path list looking for the current class and then getting the name of the
			// template after that, which is the parent template. We then takes the current template and replace the
			// call to parent with a call to the proper template name.
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

			return $fileContents;
		}else{
			throw new CoreInfo('Unable to load template ' . $name);
		}
	}

	public function isFresh($name, $time)
	{
		return filemtime($this->lastLoaded[$name]) < $time;
	}

	public function getCacheKey($name)
	{
		return md5($name);
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