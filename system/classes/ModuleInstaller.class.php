<?php

class ModuleInstaller
{
	protected $pathToPackage;
	protected $package;
	public $packageInfo;

	public function __construct($package)
	{
		if(!is_string($package))
			throw new TypeMismatch(array('String', $package, 'Must include the package name.'));

		$this->package = $package;
		$this->loadSettings();
	}

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

	public function checkRequirements()
	{
		return true;
	}

	public function installDatabaseStructure()
	{
		$sqlPath = $this->packageInfo->getPath() . 'sql/install.sql.php';
		if(file_exists($sqlPath))
		{
			$db = db_connect('default');
			$db->runFile($sqlPath);
			$this->changeStatus('dbStructure');
		}
	}

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

	public function installModels()
	{
		$models = $this->packageInfo->getModels();
		foreach($models as $model)
		{
			ModelRegistry::setHandler($model['type'], $this->package, $model['name']);
		}
	}

	public function installCustom()
	{
		if($className = importFromModule('CustomInstall', $this->package, 'plugin'))
		{
			$customInstallation = new $className($this->package);
			$customInstallation->run();
		}
	}

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

	public function changeStatus($status)
	{
		$moduleRecord = new ObjectRelationshipMapper('modules');
		$moduleRecord->package = $this->package;

		$moduleRecord->select();

		$moduleRecord->status = $status;

		$version = new Version();
		$version->fromString($this->packageInfo->getMeta('version'));

		if(is_numeric($version->major))
			$moduleRecord->majorVersion = $version->major;
		if(is_numeric($version->minor))
			$moduleRecord->minorVersion = $version->minor;
		if(is_numeric($version->micro))
			$moduleRecord->microVersion = $version->micro;

		$moduleRecord->releaseType = $version->releaseType;
		$moduleRecord->releaseVersion = $version->releaseVersion;

		$moduleRecord->query_set('lastupdated', 'NOW()');

		if(!$moduleRecord->save())
		{
			throw new BentoError('Unable to update package database with version information.');
		}else{
			return true;
		}
	}

}

?>