<?php

class TwigIntegrationThemeLoader implements Twig_LoaderInterface
{
	protected $lastLoadedList = array();
	protected $lastLoaded;
	protected $generationDelimiter = ':';
	protected $paths;

	public function loadTemplateSet($name)
	{
		if(isset($this->lastLoadedList[$name]))
			return $this->lastLoadedList[$name];


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

		$this->lastLoadedList[$name] = $templateSet;
		return $templateSet;
	}


	public function getNamePieces($name)
	{
		if($generationDelimiterPosition = strpos($name, $this->generationDelimiter))
		{
			$generation = substr($name, 0, $generationDelimiterPosition);
			$name = substr($name, $generationDelimiterPosition);
			$name = ltrim($name, ':');
			return array('name' => $name, 'generation' => $generation);
		}else{
			return array('name' => $name);
		}
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
		$namePieces = $this->getNamePieces($name);
		$genericName = $namePieces['name'];

		if(isset($this->lastLoadedList[$genericName][$name]))
		{
			$fileContents = file_get_contents($this->lastLoadedList[$genericName][$name]);
			// We're iterating through the path list looking for the current class and then getting the name of the
			// template after that, which is the parent template. We then takes the current template and replace the
			// call to parent with a call to the proper template name.

			if(preg_match('{\{% extends "(.*?)" %\}}', substr($fileContents, 0, 1024), $subs) === 1)
			{
				$parentTemplate = $subs[1];

				if($parentTemplate == 'parent' || $parentTemplate == $genericName)
				{
					$found = false;
					foreach($this->lastLoadedList[$genericName] as $className => $path)
					{
						if($found && $className != $name)
						{
							$parentTemplateRealName = $className;
							break;
						}

						$found = ($className == $name);
					}

				}else{

					$parentTemplatePieces = $this->getNamePieces($parentTemplate);

					if(isset($parentTemplatePieces['generation']))
					{
						$parentTemplateRealName = $parentTemplate;
					}else{
						$parentTemplateSet = $this->loadTemplateSet($parentTemplatePieces['name']);
						$parentTemplateTemp = array_keys($parentTemplateSet);
						$parentTemplateRealName = $parentTemplateTemp[0];
					}
				}

				$fileContents =	str_replace('{% extends "' . $parentTemplate . '" %}',
											'{% extends "' . $parentTemplateRealName . '" %}', $fileContents);
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