<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage UserInputs
 */

/**
 * This class returns the arguments or query (get) values sent by the system
 *
 * @package System
 * @subpackage UserInputs
 */
class Query
{
	/**
	 * This is a copy of the array used in order to save changes
	 *
	 * @access protected
	 * @static
	 * @var FilteredArray
	 */
	static protected $query;

	/**
	 * This function returns the current Query (Arguments or Get values)
	 *
	 * @static
	 * @return FilteredArray
	 */
	static function getQuery()
	{
		if(!self::$query)
		{
			$type = (defined('STDIN')) ? 'Argv' : 'Get';

			if(!class_exists($type, false))
			{
				if(!(include('inputHandlers/' . $type . '.class.php')))
					throw new BentoError('Unable to load input handler ' . $type);
			}

			$input = staticFunctionHack($type, 'getArray');


			$inputArray = self::processInput($input);
			self::$query = new FilteredArray($inputArray);
		}

		return self::$query;
	}

	/**
	 * Returns a Url using the current arguments (think $_SERVER['PHP_SELF'] without the XSS)
	 *
	 * @static
	 * @return Url
	 */
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
			if($site)
				$url->location = $site->getLocation();
		}

		foreach($attributes as $name => $value)
			$url->$name = $value;

		return $url;
	}

	/**
	 * This function filters the Query before outputting it, allowing for mod_rewrite tricks
	 *
	 * @static
	 * @param array $inputArray
	 * @return array
	 */
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

			$path = rtrim($path, '/');
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
				$inputArray['module'] = array_shift($pathArray);
			}
		}

	// if location exists, use it
		if(isset($inputArray['location']) && is_numeric($inputArray['location']))
		{
			$location = new Location($inputArray['location']);
		}elseif(isset($pathArray) && count($pathArray) > 0 && INSTALLMODE == false){
	// if location isn't set, find it from the path

			if(in_array($pathArray[0], array_keys(Url::$specialDirectories)))
			{
				$inputArray['type'] = Url::$specialDirectories[array_shift($pathArray)];
				$resource['type'] = $inputArray['type'];

				if(count($pathArray) > 0)
					$inputArray['id'] = array_shift($pathArray);
			}else{

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
			}



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
			$inputArray['action'] = preg_replace("/[^a-zA-Z0-9s]/", "", $inputArray['action']);

		return $inputArray;
	}
}

?>