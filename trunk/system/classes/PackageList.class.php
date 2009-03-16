<?php

class PackageList
{
	protected $installedPackages = array();
	protected $installablePackages = array();

	public function __construct()
	{
		$this->installedPackages = $this->loadInstalledPackages();
			sort($this->installedPackages, SORT_STRING);

		$this->installablePackages = array_diff($this->loadInstallablePackages(), $this->installedPackages);
			sort($this->installablePackages, SORT_STRING);
	}

	protected function loadInstallablePackages()
	{
		$info = InfoRegistry::getInstance();

		$packageDirectories = glob($info->Configuration['path']['modules'] . '*');
		$packageList = array();
		foreach ($packageDirectories as $packagePath)
		{
			// STRICT standards don't let me place the explode functions as arguments of array_pop
			// $packageName = array_shift(explode('.', array_pop(explode('/', $packagePath))));

			$tmp = explode('/', $packagePath);
			$tmp = explode('.', array_pop($tmp));
			$packageName = array_shift($tmp);
			$packageList[] = $packageName;
		}

		return $packageList;
	}

	protected function loadInstalledPackages()
	{
		if(INSTALLMODE)
			return array();

		$db = dbConnect('default_read_only');
		$results = $db->query('SELECT package FROM modules');
		$packageList = array();
		while($row = $results->fetch_assoc())
		{
			$packageList[] = $row['package'];
		}

		return $packageList;
	}

	public function getPackageList()
	{
		$fullSet = array_merge($this->installedPackages, $this->installablePackages);
		sort($fullSet, SORT_STRING);
		return $fullSet;
	}

	public function getInstalledPackages()
	{
		return $this->installedPackages;
	}

	public function getInstallablePackages()
	{
		return $this->installablePackages;
	}

}

?>