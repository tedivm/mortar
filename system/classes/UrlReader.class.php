<?php

class UrlReader
{
	protected $trailingSlash = false;
	protected $attributes = array();

	static $endingAttributes = array('module', 'type', 'id', 'action');

	public function readUrl($getValues, $domain = null)
	{
		if(isset($getValues['XDEBUG_PROFILE']))
			unset($getValues['XDEBUG_PROFILE']);

		if(isset($getValues['p']))
		{
			$this->readPath($getValues['p']);
			unset($getValues['p']);
			if(isset($getValues['path'])) unset($getValues['path']);
		}elseif(isset($getValues['path'])){
			$this->readPath($getValues['path']);
			unset($getValues['path']);
		}

		if(isset($getValues['action']))
		{
			$this->setAttribute('action', $getValues['action'], true);
			unset($getValues['action']);
		}

		foreach($getValues as $name => $value)
			$this->setAttribute($name, $value);
		return $this->attributes;
	}

	protected function readPath($path)
	{
		if(strpos($path, '?') !== false)
		{
			$tmpPathParts = explode('?', $path);
			$path = $tmpPathParts[0];
			$attributes = parse_str($tmpPathParts[1]);
		}

		if(substr($path, -1) === '/')
		{
			$path = substr($path, 0, strlen($path) - 1);
			$this->trailingSlash = true;
		}

		$pathParts = explode('/', $path);

		// keep checking for special directories until one is found
		while(isset($pathParts[0]) && $this->specialBeginningPaths($pathParts)){};

		if(!isset($this->attributes['type']))
			$this->getLocation($pathParts);

		// If there were any remaining attributes we should add them now
		if(isset($attributes))
			foreach($attributes as $name => $value)
				$this->setAttribute($name, $value);

	}

	protected function specialBeginningPaths( &$pathPieces)
	{
		$rootPiece = strtolower($pathPieces[0]);

		switch($rootPiece)
		{
			case 'admin':
				$this->setAttribute('format', 'admin');
				array_shift($pathPieces);
				return true;
				break;

			case 'direct':
				$this->setAttribute('format', 'direct');
				array_shift($pathPieces);
				return true;
				break;

			case 'rest':
				$this->setAttribute('ioHandler', 'rest');
				array_shift($pathPieces);
				return true;
				break;

			case 'module':

				array_shift($pathPieces);

				if(isset($pathPieces[0]))
				{
					$tempfamily = array_shift($pathPieces);

					if(ModuleFamily::familyExists($tempfamily))
						$family = $tempfamily;


					if(!isset($family))
					{
					// if the family doesn't exist see if it's actually a module
						$packageInfo = PackageInfo::loadByName(null, $tempfamily);

					}elseif(isset($pathPieces[0])){
					// if the family exists and there is another piece then it should be the module
						$module = array_shift($pathPieces);
						$packageInfo = PackageInfo::loadByName($family, $module);
						if($packageInfo && !($id = $packageInfo->getId()))
							unset($packageInfo);
					}

					// if the family is real and no module is present assume 'common'
					if(!isset($packageInfo) && isset($family))
						$packageInfo = PackageInfo::loadByName($family, 'common');

					if($packageInfo && !($id = $packageInfo->getId()))
						return false;

					$this->setAttribute('module', $id);

//					$this->setAttribute('module', $pathPieces[1]);
					if(isset($pathPieces[0]))
						$this->setAttribute('action', array_shift($pathPieces));

					if(isset($pathPieces[0]))
						$this->setAttribute('id', array_shift($pathPieces));

					return true;
				}else{
					return false;
				}
				break;

			case 'resources':
				if(isset($pathPieces[1]))
				{
					$this->setAttribute('type', $pathPieces[1]);
					if(isset($pathPieces[2]))
					{
						$this->setAttribute('id', $pathPieces[2]);
						array_shift($pathPieces);
					}else{
						$this->setAttribute('action', 'Index');
					}
					array_shift($pathPieces);
					array_shift($pathPieces);
					return true;
				}
		}

		if($modelType = Url::getModelFromShortcut($rootPiece))
		{
			$this->setAttribute('type', $modelType);
			if(isset($pathPieces[1]))
			{
				$this->setAttribute('id', $pathPieces[1]);
				array_shift($pathPieces);
			}else{
				$this->setAttribute('action', 'Index');
			}

			array_shift($pathPieces);
			return true;
		}

		return false;
	}

	protected function getLocation(&$pathArray)
	{
		if(defined('INSTALLMODE') && INSTALLMODE == true)
			return false;

		$site = ActiveSite::getSite();
		$currentLocation = $site->getLocation();

		foreach($pathArray as $pathIndex => $pathPiece)
		{
			$pathPiece = $this->stripFormat($pathPiece);
			if(!($childLocation = $currentLocation->getChildByName(str_replace(' ', '_', $pathPiece))))
			{
				$this->setAttribute('id', $pathPiece);
				break;
			}

			// if the current location has a child with the next path pieces name, we descend
			$currentLocation = $childLocation;
			array_shift($pathArray);
		}

		$this->setAttribute('location', $currentLocation->getId());
		return true;
	}


	protected function stripFormat($string)
	{
		$stringPos = strpos($string, '.');
		if(strpos($string, '.') !== false)
		{
			$format = substr($string, $stringPos + 1);
			$string = substr($string, 0, $stringPos);
			$this->setAttribute('format', $format, true);
		}

		return $string;
	}

	public function setAttribute($name, $value, $overwrite = false)
	{
		if(in_array($name, self::$endingAttributes))
			$value = $this->stripFormat($value);

		if($name == 'format')
			$value = ucfirst(strtolower($value));

		if($overwrite || !isset($this->attributes[$name]))
		{
			$this->attributes[$name] = $value;
			return true;
		}
		return false;
	}

}

?>