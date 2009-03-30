<?php

class Query
{
	static protected $input;

	static function getQuery()
	{
		if(!self::$input)
		{

			if(defined('STDIN'))
			{
				$input = Argv::getArray();
			}else{
				$input = Get::getArray();
			}

			$inputArray = self::processInput($input);
			self::$input = new FilteredArray($inputArray);
		}

		return self::$input;
	}

	static function getUrl()
	{
		$query = self::getQuery();
		$attributes = $query->getArrayCopy();

		$url = new Url();

		if(isset($attributes['p']))
			unset($attributes['p']);

		if(isset($attributes['format']) && $attributes['format'] == 'Html')
			unset($attributes['format']);

		if(!isset($attributes['location']))
		{
			$site = ActiveSite::getSite();
			$url->location = $site->getLocation();
		}

		foreach($attributes as $name => $value)
			$url->$name = $value;

		return $url;
	}


	static protected function processInput($inputArray)
	{

	// first lets check to see if there is a path
		if(isset($inputArray['p']))
		{
			if(strpos($inputArray['p'], '?'))
			{
				$tmp = explode('?', $inputArray['p']);
				$path = $tmp[0];
				$queryValues = explode('&', $tmp[1]);

				foreach($queryValues as $queryValue)
				{
					$tmp = explode('=', $queryValue);
					$inputArray[$tmp[0]] = $tmp[1];
				}
			}else{
				$path = $inputArray['p'];
			}

			$pathArray = explode('/', str_replace('_', ' ', $path));
			$rootPiece = strtolower($pathArray[0]);

			if($rootPiece == 'admin')
			{
				$inputArray['format'] = 'admin';
				array_shift($pathArray);
				$rootPiece = (isset($pathArray[0])) ? $pathArray[0] : null;
			}

			if($rootPiece == 'rest')
			{
				RequestWrapper::$ioHandlerType = 'Rest';
				array_shift($pathArray);
				$rootPiece = (isset($pathArray[0])) ? $pathArray[0] : null;
			}

			if($rootPiece == 'module')
			{
				// discard the 'module' tag
				array_shift($pathArray);
				// grab the name, drop it from the path
				$inputArray['module'] = strtolower(array_shift($pathArray));
			}
		}


// if location exits, use it
		if(isset($inputArray['location']) && is_numeric($inputArray['location']))
		{
			$location = new Location($inputArray['location']);
		}elseif(isset($pathArray) && count($pathArray) > 0){

// if location isn't set, find it from the path
			$site = ActiveSite::getSite();
			$currentLocation = $site->getLocation();

			foreach($pathArray as $pathIndex => $pathPiece)
			{
				if(!($childLocation = $currentLocation->getChildByName(str_replace('-', ' ', $pathPiece))))
					break;

				// if the current location has a child with the next path pieces name, we descend
				$currentLocation = $childLocation;
				array_shift($pathArray);

			}

			$inputArray['location'] = $currentLocation->getId();
			$resource = $currentLocation->getResource(true);

			if(isset($resource['type']) && is_array($pathArray) && count($pathArray) > 0)
			{
				$resourceInfo = ModelRegistry::getHandler($resource['type']);
				$urlTemplate = new DisplayMaker();

				if(($urlTemplate->loadTemplate($resource['type'] . 'UrlMapping', $resourceInfo['module']))
					|| $urlTemplate->loadTemplate('UrlMapping', $resourceInfo['module']))
				{
					$tags = $urlTemplate->tagsUsed();
					if(count($tags) > 0)
					{
						foreach($tags as $name)
							$inputArray[$name] = array_shift($pathArray);
					}
				}else{
					$inputArray['action'] = $pathArray[0];
				}
			}
		}

		if(isset($inputArray['format']))
		{
			switch(strtolower($inputArray['format']))
			{
				case 'xml':
					$inputArray['format'] = 'Xml';
					break;

				case 'rss':
					$inputArray['format'] = 'Rss';
					break;

				case 'json':
					$inputArray['format'] = 'Json';
					break;

				case 'admin':
					$inputArray['format'] = 'Admin';
					break;

				case 'html':
					$inputArray['format'] = 'Html';
					break;

				default:
					unset($inputArray['format']);
					break;
			}
		}

		if(isset($inputArray['action']))
		{
			$inputArray['action'] = preg_replace("/[^a-zA-Z0-9s]/", "", $inputArray['action']);
		}

		return $inputArray;
	}

}

?>