<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Module
 */

/**
 * This class createds a list of installed and installable packages
 *
 * @package System
 * @subpackage Module
 */
class PackageList
{
	/**
	 * This is an array of all the installed packages
	 *
	 * @var array
	 */
	protected $installedPackages;

	/**
	 * This is a list of all packages that can be installed
	 *
	 * @var array
	 */
	protected $installablePackages;

	/**
	 * This function loads a list of installable packages
	 *
	 * @access protected
	 * @return array
	 */
	protected function loadInstallablePackages()
	{
		$config = Config::getInstance();
		$packageDirectories = glob($config['path']['modules'] . '*');
		$packageList = array();
		foreach ($packageDirectories as $packagePath)
		{
			// STRICT standards don't let me place the explode functions as arguments of array_pop
			// $packageName = array_shift(explode('.', array_pop(explode('/', $packagePath))));

			$tmp = explode('/', $packagePath);
			$tmp = explode('.', array_pop($tmp));
			$packageName = array_shift($tmp);

			$meta = PackageInfo::getMetaInfo($packageName);

			if(isset($meta['disableInstall']) && $meta['disableInstall'] == true)
				continue;

			$packageList[] = $packageName;
		}

		return $packageList;
	}

	/**
	 * This function returns a list of installed packages
	 *
	 * @access protected
	 * @return array
	 */
	protected function loadInstalledPackages()
	{
		if(defined('INSTALLMODE') && INSTALLMODE)
			return array();

		$cache = CacheControl::getCache('system', 'modules', 'installed');
		$packageList = $cache->getData();

		if($cache->isStale())
		{
			$packageList = array();
			$db = dbConnect('default_read_only');
			$results = $db->query('SELECT package FROM modules WHERE status LIKE \'installed\'');
			while($row = $results->fetch_assoc())
				$packageList[] = $row['package'];

			$cache->storeData($packageList);
		}
		return $packageList;
	}

	/**
	 * Returns a full list of packages, both installed and not
	 *
	 * @return array
	 */
	public function getPackageList()
	{
		$fullSet = array_merge($this->getInstalledPackages(), $this->getInstallablePackages());
		sort($fullSet, SORT_STRING);
		return $fullSet;
	}

	/**
	 * Returns an array of installed packages
	 *
	 * @return array
	 */
	public function getInstalledPackages()
	{
                if(!isset($this->installedPackages))
		{
			$this->installedPackages = $this->loadInstalledPackages();
				sort($this->installedPackages, SORT_STRING);
		}

		return $this->installedPackages;
	}

	/**
	 * Returns an array of installable packages
	 *
	 * @return array
	 */
	public function getInstallablePackages()
	{
                if(!isset($this->installablePackages))
		{
			$this->installablePackages = array_diff($this->loadInstallablePackages(), $this->getInstalledPackages());
				sort($this->installablePackages, SORT_STRING);
		}
		return $this->installablePackages;
	}

}

?>