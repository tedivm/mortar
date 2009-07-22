<?php
/**
 * BentoBase
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
	protected $installedPackages = array();

	/**
	 * This is a list of all packages that can be installed
	 *
	 * @var array
	 */
	protected $installablePackages = array();

	/**
	 * The constructor calls the functions to load the installedPackages and installablePackages lists
	 *
	 */
	public function __construct()
	{
		$this->installedPackages = $this->loadInstalledPackages();
			sort($this->installedPackages, SORT_STRING);

		$this->installablePackages = array_diff($this->loadInstallablePackages(), $this->installedPackages);
			sort($this->installablePackages, SORT_STRING);
	}

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
		if(INSTALLMODE)
			return array();

		$db = dbConnect('default_read_only');
		$results = $db->query('SELECT package FROM modules WHERE status LIKE \'installed\'');
		$packageList = array();
		while($row = $results->fetch_assoc())
		{
			$packageList[] = $row['package'];
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
		$fullSet = array_merge($this->installedPackages, $this->installablePackages);
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
		return $this->installedPackages;
	}

	/**
	 * Returns an array of installable packages
	 *
	 * @return array
	 */
	public function getInstallablePackages()
	{
		return $this->installablePackages;
	}

}

?>