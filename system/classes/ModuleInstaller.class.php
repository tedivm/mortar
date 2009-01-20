<?php

class ModuleInstaller
{
	protected $version;
	protected $pathToPackage;
	protected $package;
	public $packageInfo;

	public function __construct($package)
	{
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


		echo 'purple';
		if(file_exists($this->pathToPackage . 'meta.php'))
		{
			include($pathToPackage . 'meta.php');
			$versionString = $version;
			var_dump($versionString);
			$version = new Version();
			$version->fromString($versionString);
			$this->version = $version;
			var_dump($version);
		}
	}

	public function fullInstall()
	{
		if($this->checkRequirements())
		{
			// Because the dbConnect function pools connections, changing this 'default' connections settings
			// changes it for everything else that gets called, allowing us to easily roll back everything but
			// additions to the database structure (new tables, indexes, foreign keys).
			$db = dbConnect('default');
			$db->autocommit(false);

			try{
				switch ($status) {
					default:
					case 'filesystem':
						$this->installDatabaseStructure();

					case 'dbStructure':
						$this->installDatabaseData();

					case 'dbData':
						$this->addPermissions();
						$this->installModels();
						$this->installPlugins();
						$this->changeStatus('installed');
				}

				$db->commit();
				$db->autocommit(true);
				return true;
			}catch(Exception $e){
				$db->rollback();
				$db->autocommit(true);
				throw new BentoError('Unable to install module ' . $this->package . ', rolling back database changes.');
			}

		}else{
			// some sort of way to show the error
		}
	}

	public function checkRequirements()
	{
		return true;
	}

	public function installDatabaseStructure()
	{
		$sqlPath = $this->pathToPackage . 'sql/install.sql.php';
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
		$modelFiles = glob($this->pathToPackage . 'models/*');
		foreach($modelFiles as $modelFile)
		{
			$action = array();
			$tmpArray = explode('/', $modelFile);
			$tmpArray = array_pop($tmpArray);
			$tmpArray = explode('.', $tmpArray);
			$modelName = array_shift($tmpArray);
			//explode, pop. explode. shift
			$className = $this->package . 'Model' . $modelName;
			$type = staticHack($className, 'type');

			$modelRegistration = new ObjectRelationshipMapper('modelsRegistered');
			$modelRegistration->name = $className;
			$modelRegistration->resource = $type;

			// This way we're updating any existing row, not adding duplicates (although the database index should
			// stop duplicates anyways).
			$modelRegistration->select();

			$modelRegistration->name = $className;
			$modelRegistration->package = $this->package;
			$modelRegistration->save();
		}
	}

	public function installPlugins()
	{
		// need to better define plugins still
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
		if(is_numeric($version->major))
			$moduleRecord->majorVersion = $version->major;
		if(is_numeric($version->minor))
			$moduleRecord->minorVersion = $version->minor;
		if(is_numeric($version->micro))
			$moduleRecord->microVersion = $version->micro;

		$moduleRecord->prereleaseType = $version->releaseType;
		$moduleRecord->prereleaseVersion = $version->releaseVersion;

		if(!$moduleRecord->save())
		{
			throw new BentoError('Unable to update package database with version information.');
		}else{
			return true;
		}
	}

}

?>