<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @deprecated
 */

/**
 * This defunct class has all sorts of information that can easily be gotten without it
 *
 * @deprecated
 * @package System
 */
class RuntimeConfig implements ArrayAccess
{
	protected $config = array();
	static $instance;

	private function __construct()
	{
		depreciationWarning();
		$this->config = $this->setData();
	}

	protected function setData()
	{
		$get = Query::getQuery();
		$data = array();

		// Path
		$path = ($get['parameters']) ? $get['parameters'] : NULL;

		if(!isset($get['package']) && !INSTALLMODE)
		{
			$pathArray = $this->convertPath($path);
			if(is_array($pathArray))
				$data = array_merge($data, $pathArray);

		}else{
			$data['package'] = $get['package'];
		}



		$data['engine'] = ((isset($get['engine'])) ? $get['engine'] : 'Html');
		$data['action'] = (isset($get['action'])) ? $get['action'] : 'Default';

		if(isset($get['id']))
			$data['id'] = $get['id'];

		if(is_numeric($get['moduleId']) && !is_numeric($data['moduleId']))
		{
			$data['moduleId'] = $get['moduleId'];
		}

		if(!isset($data['currentLocation']) && isset($get['location']))
		{
			$data['currentLocation']= $get['location'];
		}


		$location = new Location($data['currentLocation']);
		if(!isset($data['package']))
		{
			$data['package'] = $location->getMeta('default');
		}

		return $data;
	}

	protected function convertPath($pathString)
	{
		$site = ActiveSite::getSite();
		$currentLocation = $site->getLocation();

		if(strlen($pathString) > 0 )
		{
			$pathVariables = explode('/', $pathString);

			foreach($pathVariables as $pathIndex => $pathPiece)
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

		}


	 	switch ($currentLocation->getResource()) {
			case 'directory':
			case 'site':
				$moduleId = $currentLocation->getMeta('default');
				break;

			default:
				break;
			}


		if(!isset($data['package']))
		{
			$pathReturn['package'] = $currentLocation->getMeta('default');
		}


		// Dump extra path variables to our good friend 'get'
		if(count($pathVariables) > 0)
		{
			$get = Get::getInstance();
			$template = new DisplayMaker();

			if(!(isset($pathReturn['package']) && $template->loadTemplate('url', $pathReturn['package'])))
			{
				$template->setDisplayTemplate('{# action #}/{# id #}/');
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



	public static function getInstance()
	{
		if(!isset(self::$instance)){
			$object = __CLASS__;
			self::$instance = new $object;
		}
		return self::$instance;
	}

	public function offsetExists($offset)
	{
		if(isset($this->config[$offset]))
		{
			return true;
		}

		return false;

	}

	public function offsetGet($offset)
	{
		return $this->config[$offset];
	}


	public function offsetSet($key, $value)
	{
		$this->config[$key] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->config[$offset]);
		return true;
	}

}

?>