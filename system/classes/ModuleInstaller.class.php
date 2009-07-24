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
 * This class installs a module from its package
 *
 * @package System
 * @subpackage Module
 */
class ModuleInstaller
{
	/**
	 * This is a path to the package being installed
	 *
	 * @access protected
	 * @var unknown_type
	 */
	protected $pathToPackage;

	/**
	 * This is the name of the package being installed
	 *
	 * @access protected
	 * @var string
	 */
	protected $package;

	/**
	 * This is the package info file for the package being installed
	 *
	 * @var PackageInfo
	 */
	public $packageInfo;

	/**
	 * This constructor sets up the information needed for installation
	 *
	 * @param string $package
	 */
	public function __construct($package)
	{
		if(!is_string($package))
			throw new TypeMismatch(array('String', $package, 'Must include the package name.'));

		$this->package = $package;
		$this->loadSettings();
	}

	/**
	 * This function loads the settings for the module from its package
	 *
	 * @access protected
	 */
	protected function loadSettings()
	{
		$info = InfoRegistry::getInstance();
		$pathToPackage = $info->Configuration['path']['modules'] . $this->package;

		if(!is_dir($pathToPackage))
			throw new BentoError('Unable to load package ' . $this->package . ' for installation.');

		$this->packageInfo = new PackageInfo($this->package);
		$this->pathToPackage = $pathToPackage;
		$this->changeStatus('fileSystem');
	}

	/**
	 * This function attempts a full installation of the module
	 *
	 * @return bool
	 */
	public function fullInstall()
	{
		try{
			if($this->checkRequirements())
			{
				// Because the dbConnect function pools connections, changing this 'default' connections settings
				// changes it for everything else that gets called, allowing us to easily roll back everything but
				// additions to the database structure (new tables, indexes, foreign keys).
				$db = dbConnect('default');
				$db->autocommit(false);

				try{
					switch ($this->packageInfo->status)
					{
						default:
						case 'filesystem':
							$this->installDatabaseStructure();

						case 'dbStructure':
							$this->installDatabaseData();

						case 'dbData':
							$this->addPermissions();
							$this->installModels();
							$this->installCustom();
							$this->changeStatus('installed');
					}

					$db->commit();
					$db->autocommit(true);
					return true;
				}catch(Exception $e){
					$db->rollback();	// problem, this could erase the status, which means the database structure
										// would be set up but the system wouldn't know
					$db->autocommit(true);
					throw new BentoError('Unable to install module ' . $this->package . ', rolling back database changes.');
				}

			}else{
				// some sort of way to show the error
			}
		}catch(Exception $e){
			return false;
		}
		return true;
	}

	/**
	 * This function checks to make sure requirements are met for the package
	 *
	 * @todo write this
	 * @return bool
	 */
	public function checkRequirements()
	{
		return true;
	}

	/**
	 * This sets up the database structure by checking the package for an installation.sql.php file.
	 *
	 */
	public function installDatabaseStructure()
	{
		$sqlPath = $this->packageInfo->getPath() . 'sql/install.sql.php';
		if(file_exists($sqlPath))
		{
			$db = db_connect('default');
			if($db->runFile($sqlPath) === false)
				throw new BentoError('Unable to install database structure.');

			$this->changeStatus('dbStructure');
		}
	}

	/**
	 * Here we add the data to the database, if the install_data.sql.php file is present
	 *
	 */
	public function installDatabaseData()
	{
		$sqlPath = $this->pathToPackage . 'sql/install_data.sql.php';
		if(file_exists($sqlPath))
		{
			$db = db_connect('default');
			$db->runFile($sqlPath);
			$this->changeStatus('dbData');
		}
	}

	/**
	 * Here we register any models this module has
	 *
	 */
	public function installModels()
	{
		$models = $this->packageInfo->getModels();
		foreach($models as $model)
		{
			ModelRegistry::setHandler($model['type'], $this->package, $model['name']);
		}
	}

	/**
	 * If the package has a custom installer we open it up and run its code.
	 *
	 */
	public function installCustom()
	{
		if($className = importFromModule('CustomInstall', $this->package, 'plugin'))
		{
			$customInstallation = new $className($this->package);
			$customInstallation->run();
		}
	}

	/**
	 * Here we add all of the new permission types into the system
	 *
	 * @return bool
	 */
	public function addPermissions()
	{
		$actions = $this->packageInfo->getActions();
		$permissions = array();
		foreach($actions as $action)
		{
			if(strlen($action['permissions']) > 2 )
				$permissions[] = $action['permissions'];
		}

		$permissions = array_unique($permissions);
		foreach($permissions as $permission)
		{
			PermissionActionList::addAction($permission);
		}
		return true;
	}

	/**
	 * This function changes the status of the package so that installations can be resumed
	 *
	 * @param string $status
	 * @return bool
	 */
	public function changeStatus($status)
	{
		$moduleRecord = new ObjectRelationshipMapper('modules');
		$moduleRecord->package = $this->package;
		$moduleRecord->select();
		$moduleRecord->status = $status;

		$versionString = $this->packageInfo->getMeta('version');
		if(!$versionString)
			$versionString = '0.0.0';

		$version = new Version();
		$version->fromString($versionString);

		if(is_numeric($version->major))
			$moduleRecord->majorVersion = $version->major;
		if(is_numeric($version->minor))
			$moduleRecord->minorVersion = $version->minor;
		if(is_numeric($version->micro))
			$moduleRecord->microVersion = $version->micro;

		$moduleRecord->releaseType = $version->releaseType;
		$moduleRecord->releaseVersion = $version->releaseVersion;
		$moduleRecord->lastupdated = gmdate('Y-m-d H:i:s');
		$moduleRecord->querySet('lastupdated', 'NOW()');

		if(!$moduleRecord->save())
		{
			throw new BentoError('Unable to update package database with version information.');
		}else{
			return true;
		}
	}

}

?>