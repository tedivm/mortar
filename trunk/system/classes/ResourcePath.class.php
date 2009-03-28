<?php

class ResourcePath
{
	protected $location;

	protected $site;

	protected $engine;

	protected $url;

	protected $module;

	protected $string;

	public function loadFromString($pathString, $site = null)
	{
		$site = ($site) ? $site : ActiveSite::getSite();

		$pathVariables = $this->setLocation(explode('/', $pathString), $site->getLocation());

	 	switch ($currentLocation->getResource()) {
			case 'directory':
			case 'site':
				$moduleId = $currentLocation->meta('default');
				break;

			default:
				break;
			}


		if(!isset($data['package']))
		{
			$pathReturn['package'] = $currentLocation->meta('default');
		}


		// Dump extra path variables to our good friend 'get'
		if(count($pathVariables) > 0)
		{
			$get = Get::getInstance();
			$template = new DisplayMaker();

			if(!(isset($pathReturn['package']) && $template->load_template('url', $pathReturn['package'])))
			{
				$template->set_display_template('{# action #}/{# id #}/');
			}

			$tags = $template->tagsUsed();
			foreach($tags as $tag)
			{
				$variable = array_shift($pathVariables);
				if(strlen($variable) > 0)
					$get[$tag] = $variable;
			}
		}

		unset($pathReturn['package']);

		$pathReturn['currentLocation'] = $currentLocation->getId();
		return $pathReturn;
	}


	protected function setLocation($pathArray, $startLocation)
	{
		$currentLocation = $startLocation;

		$pathArray = $this->rootAliases($pathArray);

		if(!is_array($pathArray))
		{
			// do something
		}

		foreach($pathArray as $pathIndex => $pathPiece)
		{





			if(!($childLocation = $currentLocation->getChildByName(str_replace('_', ' ', $pathPiece))))
			{





				break;
			}

			switch (strtolower($childLocation->getResource()))
			{
				case 'directory':
					$currentLocation = $childLocation;
					break;

				default:
					break 2;
			}
			unset($pathVariables[$pathIndex]);
		}


		$this->location = $currentLocation;

	}

	protected function rootAliases($pathArray)
	{
		$string = $pathArray[0];


		switch(strtolower($string))
		{
			case 'admin':
				return false;
				break;

			case 'rest':
				array_shift($string);
				break;

		}

		return $pathArray;
	}

}

?>