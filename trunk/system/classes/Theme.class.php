<?php

class Theme
{
	public $name;
	
	protected $cssPath;
	protected $jsPath;
	protected $formPath;
	
	protected $cssFiles;
	protected $jsFiles;
	protected $formFiles;
	
	protected $url;
	
	public function __construct($name)
	{
		$this->name = $name;
		$info = InfoRegistry::getInstance();
		
		$cache = new Cache('themes', $name, 'themeinfo');
		
		$data = $cache->getData();
		
		if(!$cache->cacheReturned)
		{
			
			$info = InfoRegistry::getInstance();
			
			$path = $info->Configuration['path']['theme'] . $name . '/';
			
			if(file_exists($path))
			{
				
				$data['path']['javascript'] = $path . 'javascript/';
				$javascriptFiles = $this->getFiles($data['path']['javascript']);
				foreach($javascriptFiles as $file)
				{
					$library = $file[0];
					$plugin = $file[1];
					$compression = ($file[2] == 'min');
					
					$data['javascript'][$library][$plugin] = $compression;
				}
				
				
				$data['path']['forms'] = $path . 'forms/';
				$formFiles = $this->getFiles($data['path']['forms']);
				foreach($formFiles as $file)
				{
					$extension = array_pop($file);
					$name = $file[0];
					$data['forms'][$name][$extension] = true; 
				}
				
				
				$data['path']['css'] = $path . 'css/';
				$cssFiles = $this->getFiles($data['path']['css']);
				foreach($cssFiles as $file)
				{
					$extension = array_pop($file);
					if($extension == 'css')
					{
						switch (count($file)) {
							case 1:
								$file[1] = $file[0];
								$file[0] = 'user';
								break;
							case 2:
								$data['css'][$file[0]][$file[1]];
								break;
						
							default:	
								break 2;	// just skip it, since it follows no naming convention to speak of
						}
					}
					$data['css'] = $file;
				}
								
				
			}else{
				//doom
			}
			
			$cache->storeData($data);
		}
		
		
		
		$this->url = $info->Site->currentLink . $info->Configuration['url']['theme'] . $this->name . '/';
		
		$this->jsPath = $data['path']['javascript'];
		$this->cssPath = $data['path']['css'];
		$this->formPath = $data['path']['forms'];
		
		$this->jsFiles = $data['javascript'];
		$this->formFiles = $data['forms'];
		$this->cssFiles = $data['css'];
	}

	public function jsUrl($plugin, $library = 'jquery')
	{
		if(isset($this->jsFiles[$library][$plugin]))
		{
			$compression = ($this->jsFiles[$library][$plugin]) ? '.min' : '';
			return $this->url . 'javascript/' . $library . '.' . $plugin . $compression . '.js';
		}
		return false;
	}	
	
	public function cssUrl($name, $extension)
	{
		return (in_array($name, $this->cssFiles)) ? $this->url . 'css/' . $name . '.css' : false;
	}
	
	
	public function formResource($name, $extension)
	{
		return ($this->formFiles[$name][$extension]) ? $this->url . 'forms/' . $name . '.' . $extension : false;
	}
	
	
	protected function getFiles($path)
	{
		$pattern = glob($path . '*');
		$fileArray = array();
		foreach($pattern as $file)
		{
			$fullFile = array_pop(explode('/', $file));
			$fileArray[] = explode('.', $fullFile);
		}
		return $fileArray;
	}
	
	
}



?>