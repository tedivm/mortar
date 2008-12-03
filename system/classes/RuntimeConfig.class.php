<?php
class RuntimeConfig implements ArrayAccess 
{
	protected $config = array();
	static $instance;
		
	private function __construct()
	{
		$get = Get::getInstance();
		$this->config['engine'] = ((isset($get['engine'])) ? $get['engine'] : 'Html');
		$this->config['action'] = (isset($get['action'])) ? $get['action'] : 'Default';
		$this->config['moduleId'] = $get['moduleId'];
		
		
		try{
			
			if(is_numeric($this->config['moduleId']))
			{				
				$moduleInfo = new ModuleInfo($get['moduleId']);
				$this->config['package'] = $moduleInfo['Package'];
				$this->config['packagePath'] = $moduleInfo['PathToPackage'];
				
			}elseif(isset($get['package'])){

				$packageInfo = new PackageInfo($get['package']);
				$this->config['package'] = $packageInfo->getName();
				$this->config['packagePath'] = $packageInfo->getPath();
				
			}
			
		}catch(Exception $e){
			
		}
		
		$this->config['siteId'] = $get['siteId'];
		$this->config['id'] = $get['id'];
		
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