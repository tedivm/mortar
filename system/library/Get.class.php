<?php

class Get extends Post
{
	public $variables = array();
	static $instance;

	private function __construct()
	{
		$_GET = get_magic_quotes_gpc() ? array_map('stripslashes', $_GET) : $_GET;
		$this->variables = $_GET;
		$config = Config::getInstance();
		

		if(isset($_GET['module']) && is_int($_GET['module']))
		{
			$this->variables['moduleId'] = $_GET['module'];
			
			$moduleInfo = new ModuleInfo($_GET['module']);
		}

		

		if(isset($_GET['parameters']))
		{
			$variable_split = explode('?', $_GET['parameters']);
			$pathVariables = explode('/', $variable_split['0']);
			$queryVariables = explode('&', $variable_split['1']);
			
			// Run through everything after the ? so that get values work even on rewritten urls
			foreach($queryVariables as $variable)
			{
				$ts = explode('=', $variable);
				$this->variables[$ts[0]] = $ts[1];
			}		
			unset($queryVariables);
	
		}

			
		
		if(!$config->error)
		{
			
			$site = ActiveSite::get_instance();
			$currentLocation = $site->location;

			if(isset($pathVariables) && !is_numeric($this->variables['moduleId']))
			{
				$pathArray = array();
				foreach($pathVariables as $pathIndex => $pathPiece)
				{
					if($child = $currentLocation->getChildByName($pathPiece))
					{
						
						if($child->resource == 'alias')
						{

								$alias = new Alias(); //some sort of alias loading thing.
								
								switch($alias->type)
								{
									case 'location':
										$child = new Location($alias->locationId);
										break;
								
								}								
						}
						
						switch ($child->resource_type())
						{
							case 'directory':
								$pathArray[] = $pathPiece;
								unset($pathVariables[$pathIndex]);
								$currentLocation = $child;
								break;
								
							case 'module':		
							case 'Module':
								$moduleInfo = new ModuleInfo($child->getId(), 'location');
								$pathArray[] = $pathPiece;
								
								$this->variables['moduleId'] = $moduleInfo->getId();
								unset($pathVariables[$pathIndex]);
								break 2; //break out of foreach loop
								
							default:
								break 2; //break out of foreach loop
						}
					}else{
						break; //break out of foreach loop
					}
					
					
					
				}//foreach($pathVariables as $pathIndex => $pathPiece)				
				
				$this->variables['pathArray'] = $pathArray;
			}

				
			
			
			// if the directory exists but the module isn't set, check to see if there is a default
			if(!isset($moduleInfo) && !isset($this->variables['package']) && (($currentLocation->resource == 'directory' || $currentLocation->resource == 'site') && is_numeric($currentLocation->meta('default'))))
			{
				$moduleInfo = new ModuleInfo($currentLocation->meta('default'));
			}
			
			
			
			// Map any remaining path variables to their respective name
			if(isset($moduleInfo))
			{
				$this->variables['moduleId'] = $moduleInfo['ID'];
				$this->variables['package'] = $moduleInfo['Package'];
				$package = $moduleInfo['Package'];
			}elseif($this->variables['package']){
				$package = $this->variables['package'];
			}
		
			
			
			
			if(count($pathVariables) > 0)
			{
				$template = new DisplayMaker();
				$template->load_template('url', $package);
					
				if(!$template->load_template('url', $package))
				{
					$template->set_display_template('{# id #}/{# action #}/');
				}					
					
				$tags = $template->tagsUsed();
				foreach($tags as $tag)
				{
					$this->variables[$tag] = array_shift($pathVariables);
				}
			}			
			
			
			
		}//if(isset($_GET['parameters']))
		
	}
	
	public static function getInstance()
	{
		if(!isset(self::$instance)){
			$object = __CLASS__;
			self::$instance = new $object();			
		}
		return self::$instance;
	}	
}


?>