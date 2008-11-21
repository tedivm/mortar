<?php

class PackageList
{
	protected $packages = array();
	
	public function __construct()
	{
		$this->loadPackages();
	}
	
	protected function loadPackages()
	{
		$info = InfoRegistry::getInstance();
		
		$packageDirectories = glob($info->Configuration['path']['packages'] . '*');
		$packageList = array();
		foreach ($packageDirectories as $packagePath)
		{
			$packageName = array_shift(explode('.', array_pop(explode('/', $packagePath))));
			$packageList[$packageName] = new PackageInfo($packageName);
		}
		
		$this->packages = $packageList;
	}
	
	public function getPackageList()
	{
		return array_keys($this->packages);
	}
	
	public function getPackageDetails()
	{
		return $this->packages;
	}
	
}

?>