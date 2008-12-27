<?php
class RuntimeConfig implements ArrayAccess
{
	protected $config = array();
	static $instance;

	private function __construct()
	{
		$this->config = $this->setData();
	}

	protected function setData()
	{
		$get = Get::getInstance();
		$data = array();

		// Path
		$path = ($get['parameters']) ? $get['parameters'] : NULL;
		$pathArray = $this->convertPath($path);

		if(is_array($pathArray))
			$data = array_merge($data, $pathArray);


		$data['engine'] = ((isset($get['engine'])) ? $get['engine'] : 'Html');
		$data['action'] = (isset($get['action'])) ? $get['action'] : 'Default';

		if(isset($get['id']))
			$data['id'] = $get['id'];

		if(is_numeric($get['moduleId']) && !is_numeric($data['moduleId']))
		{
			$data['moduleId'] = $get['moduleId'];
		}

		if(is_numeric($data['moduleId']))
		{
			$moduleInfo = new ModuleInfo($data['moduleId']);
			$data['package'] = $moduleInfo['Package'];
		}

		// Package
		if(!isset($data['package']) && isset($get['package']))
		{
			$data['package'] = $get['package'];

		}

		if(isset($data['package']))
		{
			$packageInfo = new PackageInfo($data['package']);
			$data ['pathToPackage']= $packageInfo->getPath();
		}

		if(!isset($data['currentLocation']) && isset($get['location']))
		{
			$data['currentLocation']= $get['location'];
		}



//		var_dump($data);
		return $data;
	}

	protected function convertPath($pathString)
	{
		$site = ActiveSite::getInstance();
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

					case 'module':
						$currentLocation = $childLocation;
						$moduleInfo = new ModuleInfo($childLocation->getId(), 'location');
						$moduleId = $moduleInfo->getId();
						unset($pathVariables[$pathIndex]);
						break 2; //break out of foreach loop

					default:
						break 2;
				}
				unset($pathVariables[$pathIndex]);
			}

		}


	 	switch ($currentLocation->getResource()) {
			case 'directory':
			case 'site':
				$moduleId = $currentLocation->meta('default');
				break;

			default:
				break;
			}


		if(is_numeric($moduleId))
		{
			//echo $moduleId;
			$moduleInfo = new ModuleInfo($moduleId);
			$pathReturn['package'] = $moduleInfo['Package'];
			$pathReturn['moduleId'] = $moduleInfo->getId();
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