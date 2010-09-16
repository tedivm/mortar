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
		$familyDirectories = glob($config['path']['modules'] . '*');
		$packageList = array();

		$installedPackages = $this->getInstalledPackages();

		foreach ($familyDirectories as $familyPath)
		{
			if(file_exists($familyPath . '/package.ini'))
			{
				$meta = PackageInfo::getMetaInfo($familyPath);

				if(isset($meta['disableInstall']) && $meta['disableInstall'] == true)
					continue;

				$tmp = explode('/', $familyPath);
				$packageName = array_pop($tmp);

				if(!isset($installedPackages['orphan']) || !in_array($packageName, $installedPackages['orphan']))
					$packageList['orphan'][] = $packageName;

			}else{

				$packageDirectories = glob($familyPath . '*');

				foreach($packageDirectories as $packagePath)
				{
					$tmp = explode('/', $packagePath);

					$packageName = array_pop($tmp);
					$familyName = array_pop($tmp);

					if($familyName == 'modules')
						$familyName = 'orphan';

					$meta = PackageInfo::getMetaInfo($packagePath);

					if(isset($meta['disableInstall']) && $meta['disableInstall'] == true)
						continue;


					if(!isset($installedPackages[$familyName])
					   || !in_array($packageName, $installedPackages[$familyName]))
						$packageList[$familyName][] = $packageName;
				}
			}
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
			$results = $db->query('SELECT package, family FROM modules WHERE status LIKE \'installed\'');
			while($row = $results->fetch_assoc())
			{
				$family = (!isset($row['family'])) ? $row['family'] : 'orphan';
				$packageList[$family][] = $row['package'];
			}

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
		$fullSet = array_merge_recursive($this->getInstalledPackages(), $this->getInstallablePackages());
		$fullSet = self::moduleSort($fullSet);
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
			$this->installedPackages = self::moduleSort($this->loadInstalledPackages());

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
			$this->installablePackages = self::moduleSort($this->loadInstallablePackages());

		return $this->installablePackages;
	}

	static protected function moduleSort($array)
	{
		$newArray = array();
		foreach($array as $key => $value)
		{
			sort($value, SORT_STRING);
			$newArray[$key] = $value;
		}

		ksort($newArray, SORT_STRING);
		return $newArray;
	}

}

?>