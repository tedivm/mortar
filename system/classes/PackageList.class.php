<?php

class PackageList
{
	protected $packages = array();

	public function __construct($installedOnly = false)
	{
		if($installedOnly)
		{
			$this->loadInstalledPackages();
		}else{
			$this->loadPackages();
		}

	}

	protected function loadPackages()
	{
		$info = InfoRegistry::getInstance();

		$packageDirectories = glob($info->Configuration['path']['modules'] . '*');
		$packageList = array();
		foreach ($packageDirectories as $packagePath)
		{
			$packageName = array_shift(explode('.', array_pop(explode('/', $packagePath))));
			$packageList[$packageName] = new PackageInfo($packageName);
		}

		$this->packages = $packageList;
	}

	protected function loadInstalledPackages()
	{
		$db = dbConnect('default_read_only');
		$results = $db->query('SELECT package FROM modules');
		$packageList = array();
		while($row = $results->fetch_assoc())
		{
			$packages[] = $row['package'];
			$packageList[$row['package']] = new PackageInfo($row['package']);
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