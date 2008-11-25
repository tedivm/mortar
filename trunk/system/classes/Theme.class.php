<?php

class Theme
{
	public $name;
	
	protected $cssUrls;
	protected $jsUrls;
	
	protected $url;	
	protected $allowMin = true;

	public function __construct($name)
	{
		$this->name = $name;
		$info = InfoRegistry::getInstance();
		
		$cache = new Cache('theme', $this->name, $info->Site->currentLink);
		$data = $cache->getData();
		
		if(!$cache->cacheReturned)
		{
			$baseModulePath = $info->Configuration['path']['modules'];
			$baseModuleUrl = $info->Configuration['url']['modules'];
			
			$baseModulePath = $info->Configuration['path']['modules'];
			$baseModuleUrl = $info->Configuration['url']['modules'];		
			
			
			$packageList = new PackageList(true);
			$packages = $packageList->getPackageList();
			
			$javascriptLinks = array();
			$cssLinks = array();
			
			foreach($packages as $package)
			{
				$packagePath = $baseModulePath . $package . '/';
				$packageUrl = $baseModuleUrl . $package . '/';
	
				// javascript
				$javascriptResult = $this->getFiles($packagePath . 'javascript/', $packageUrl . 'javascript/', 'js');
				if($javascriptResult)
					$javascriptLinks = array_merge_recursive($javascriptLinks, $javascriptResult);
					
				// css
				$cssResult = $this->getFiles($packagePath . 'css/', $packageUrl . 'css/', 'css');
				if($cssResult)
					$cssLinks = array_merge_recursive($cssLinks, $cssResult);
				
			}
			
			
			$themePath = $info->Configuration['path']['theme'] . $name . '/';
			$themeUrl = $info->Site->currentLink . $info->Configuration['url']['theme'] . $this->name . '/';
			
			
			// javascript
			$javascriptResult = $this->getFiles($themePath . 'javascript/', $themeUrl . 'javascript/', 'js');
			if($javascriptResult)
				$javascriptLinks = array_merge_recursive($javascriptLinks, $javascriptResult);
			
			// css
			$cssResult = $this->getFiles($themePath . 'css/', $themeUrl . 'css/', 'css');
			if($cssResult)
				$cssLinks = array_merge_recursive($cssLinks, $cssResult);
				
			$bentoJavascriptPath = $info->Configuration['path']['javascript'];
			$bentoJavascriptUrl = $info->Site->getLink('javascript');	
				
			// javascript
			// This code loads the javascript that ships with Bento- we load it last so it overrides any
			// javascript in the modules. Since we only store libraries here, and all modules use those libraries,
			// we don't want modules or themes to be able to overwrite those specific ones.
			$javascriptResult = $this->getFiles($bentoJavascriptPath, $bentoJavascriptUrl, 'js');
			if($javascriptResult)
				$javascriptLinks = array_merge_recursive($javascriptLinks, $javascriptResult);
				
			$data['cssLinks'] = $cssLinks;
			$data['jsLinks'] = $javascriptLinks;
			
			$cache->storeData($data);
		}		
		
		$this->jsUrls = $data['jsLinks'];
		$this->cssUrls = $data['cssLinks'];
	}
	
	protected function loadUrl($type, $name, $library)
	{
		$filesAttribute = $type . 'Urls';
		if(isset($this->{$filesAttribute}[$library][$name]))
		{
			$output = ($this->allowMin && isset($this->{$filesAttribute}[$library][$name]['minLink'])) ? 
															$this->{$filesAttribute}[$library][$name]['minLink'] :
															$this->{$filesAttribute}[$library][$name]['mainLink'];
			return $output;
		}else{
			return false;
		}
	}
	
	
	
	public function jsUrl($name, $library = 'none')
	{
		return $this->loadUrl('js', $name, $library);
	}	
	
	public function cssUrl($name, $library = 'none')
	{
		return $this->loadUrl('css', $name, $library);
	}
	
	protected function getFiles($path, $url, $extention = '.*')
	{
		$pattern = glob($path . '*' . $extention);
		$fileArray = array();
		foreach($pattern as $file)
		{
			$fileName = array_pop(explode('/', $file));
			$fileDetails = explode('.', $fileName);
			$min = false;
			
			$extension = array_pop($fileDetails);
			
			$library = array_shift($fileDetails);
			
			switch (count($fileDetails))
			{
				case 0:
					$name = $library;
					$library = 'none';
					break;
					
				case 2:
					$option = array_pop($fileDetails);
					if($option = 'min')
					{
						$min = true;
					}
				
				case 1:
					$name = array_pop($fileDetails);
					break;	
					
			}
			
			// name, library, extension, min
			
			if($min)
			{
				$fileArray[$library][$name]['minLink'] = $url . $fileName;
			}else{
				$fileArray[$library][$name]['mainLink'] = $url . $fileName;
			}
			
		}
		return $fileArray;
	}
	
	
}



?>