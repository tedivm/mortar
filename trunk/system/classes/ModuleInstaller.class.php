<?php

class ModuleInstaller
{
	/**
	 * This is the package information for the final version of the update or installation
	 *
	 * @var PackageInfo
	 */
	protected $packageInfo;

	/**
	 * This is the path to the new package.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The version being installed.
	 *
	 * @var Version
	 */
	protected $installVersion;

	/**
	 * The version currently installed.
	 *
	 * @var Version
	 */
	protected $startVersion;

	public function __construct(PackageInfo $package, $path = null)
	{
		AutoLoader::addModule($package);

		if(!isset($path))
			$path = $package->getPath();

		if(!is_dir($path))
			throw new ModuleInstallerError('Unable to find package at ' . $path);

		$this->packageInfo = $package;
		$this->path = $path;
		$iniPath = $path . 'package.ini';
		$this->installVersion = $package->getVersion(false);
	}

	public function integrate()
	{
		try{
			// Because the dbConnect function pools connections, changing this 'default' connections settings
			// changes it for everything else that gets called, allowing us to easily roll back everything but
			// additions to the database structure (new tables, indexes, foreign keys).
			$db = DatabaseConnection::getConnection('default');
//			$db->autocommit(false);

			$alreadyPresent = false;

			if($id = $this->packageInfo->getId())
			{
				$alreadyPresent = true;
				$moduleStatus = $this->packageInfo->getStatus();
				$version = $this->packageInfo->getVersion();
				$versionInt = $version->toInt();
			}else{
				$version = new Version();
				$versionInt = 0;
			}



			$stmt = DatabaseConnection::getStatement('default');
			$stmt->prepare('SELECT * FROM schemaVersion WHERE package = ?');
			$stmt->bindAndExecute('s', $this->packageInfo->getFullName());

			$dbVersion = new Version();
			$schemaStatus = false;
			$dbVersionInt = 0;
			if($stmt->num_rows > 0 && $row = $stmt->fetch_array())
			{
				if(isset($row['majorVersion']))
					$dbVersion->major = $row['majorVersion'];

				if(isset($row['minorVersion']))
					$dbVersion->minor = $row['minorVersion'];

				if(isset($row['microVersion']))
					$dbVersion->micro = $row['microVersion'];

				if(isset($row['releaseType']))
					$dbVersion->releaseType = $row['releaseType'];

				if(isset($row['releaseVersion']))
					$dbVersion->releaseVersion = $row['releaseVersion'];


				$dbVersionInt = $dbVersion->toInt();
				$schemaStatus = isset($row['status']) ? $row['status'] : false;
				$alreadyPresent = true;
			}


			$lowestVersion = $dbVersion->compare($version) < 0 ? $dbVersion : $version;
			$this->startVersion = $lowestVersion;

			if($this->installVersion->compare($lowestVersion) === 0 && $moduleStatus != 'installed')
			{
				// resume installation
				$this->newInstall($schemaStatus, $moduleStatus);
			}elseif($alreadyPresent){
				// update
				$this->runUpdates($versionInt, $dbVersionInt, $schemaStatus, $moduleStatus);
			}else{
				// fresh install
				$this->newInstall(false, false);
			}

			$this->addPermissions();
			$this->installModels();

			$this->setVersion($this->installVersion, 'installed');

			$db->commit();
			$db->autocommit(true);
			return true;

		}catch(Exception $e){
			$db->rollback();
			$db->autocommit(true);

			new ModuleInstallerInfo('Unable to install module ' . $this->packageInfo->getFullName() . ', rolling back database changes.');
			return false;
		}

		return true;
	}

	protected function getUpdateList()
	{
		$path = $this->path . 'updates/';

		$updatePaths = glob($path . '*', GLOB_ONLYDIR);
		$updatePackages = array();

		foreach($updatePaths as $folder)
		{
			$realFolder = substr($folder, strrpos($folder, '/'));
			$realFolder = trim($realFolder, '/');
			$folder = $folder . '/';

			$updateVersion = new Version();
			$updateVersion->fromString(str_replace('_', ' ', $realFolder));


			$version = $updateVersion->toInt();

			// skip updates we aren't going to use
			// we grab the current version in case we're continuing a partial update
			if($this->startVersion->compare($updateVersion) == 1)
				continue;

			$updatePackages[$version]['path'] = $folder;

			$updatePackages[$version]['sqlstructure'] = (bool) file_exists($folder . 'structure.sql');
			$updatePackages[$version]['sqldata'] = (bool) file_exists($folder . 'data.sql');
			$updatePackages[$version]['prescript'] = (bool) file_exists($folder . 'pre.php');
			$updatePackages[$version]['postscript'] = (bool) file_exists($folder . 'post.php');

			$updatePackages[$version]['folder'] = $realFolder;
			$updatePackages[$version]['path'] = $folder;
			$updatePackages[$version]['versionString'] = str_replace('_', ' ', $realFolder);
			$updatePackages[$version]['version'] = $updateVersion;
		}

		return $updatePackages;

	}

	protected function newInstall($schemaStatus = false, $moduleStatus = false)
	{
		$path = $this->path . 'install/';
		$db = DatabaseConnection::getConnection('default');

		$this->setVersion($this->installVersion, 'prepped');
		// reload the same PackageInfo, only this time it'll be able to pull an ID in for use by other scripts
		$this->packageInfo = PackageInfo::loadByPath($this->packageInfo->getPath());

		$pathPre = $path . 'pre.php';
		if(file_exists($pathPre) && ($moduleStatus === false || !in_array($moduleStatus, array('prescript', 'postscript'))))
		{
			$classname = $this->packageInfo->getFullName() . 'InstallerPrescript';
			include($pathPre);
			if(class_exists($classname, false))
			{
				$installPreScript = new $classname();
				$installPreScript->run();
			}
			$this->setVersion($this->installVersion, 'prescript');
		}

		$pathSqlStructure = $path . 'structure.sql';
		if(file_exists($pathSqlStructure) && ($schemaStatus === false || !in_array($schemaStatus, array('structure', 'full'))))
		{
			$db->runFile($pathSqlStructure);
			$this->setVersion($this->installVersion, 'structure', false);
			$db->commit();
		}

		$pathSqlData = $path . 'data.sql';
		if(file_exists($pathSqlData) && ($schemaStatus === false || $schemaStatus !== 'full'))
		{
			$db->runFile($pathSqlData);
			$this->setVersion($this->installVersion, 'full', false);
			$db->commit();
		}

		$pathPost = $path . 'post.php';
		if(file_exists($pathPost) && ($moduleStatus === false || $moduleStatus != 'postscript'))
		{
			$classname = $this->packageInfo->getFullName() . 'InstallerPostscript';
			include($pathPost);
			if(class_exists($classname, false))
			{
				$installPreScript = new $classname();
				$installPreScript->run();
			}

		}

		$this->setVersion($this->installVersion, 'postscript');
		return true;
	}

	protected function runUpdates($version = 0, $dbVersion = 0, $schemaStatus, $moduleStatus = false)
	{
		$updates = $this->getUpdateList();
		$db = DatabaseConnection::getConnection('default');

		foreach($updates as $updateVersion => $updateInfo)
		{
			$sanatizedVersionString = str_replace(array('.', '-', ' '), '_', $updateInfo['folder']);

			if($updateInfo['prescript']
				&& ($version < $updateVersion
					|| ($version == $updateVersion
						&& $moduleStatus !== false
						&& in_array($moduleStatus, array('prescript', 'postscript', 'installed')) )))
			{
				$path = $updateInfo['path'] . 'pre.php';
				$classname = $this->packageInfo->getFullName() . 'UpdatePrescript_' . $sanatizedVersionString;

				if(file_exists($path))
				{
					include($path);
					if(class_exists($classname, false))
					{
						$UpdatePreScript = new $classname();
						$UpdatePreScript->run();
					}
				}
			}

			$this->setVersion($updateInfo['version'], 'prescript');

			if($dbVersion < $updateVersion && $schemaStatus != 'full')
			{
				if($updateInfo['sqlstructure'] && $schemaStatus != 'structure')
				{
					$path = $updateInfo['path'] . 'structure.sql';
					$db->runFile($path);
				}

				$this->setVersion($updateInfo['version'], 'structure', false);
				$db->commit();

				if($updateInfo['sqldata'])
				{
					$path = $updateInfo['path'] . 'data.sql';
					$db->runFile($path);
				}

				$this->setVersion($updateInfo['version'], 'full', false);
				$db->commit();
			}
			$schemaStatus = false;

			if($updateInfo['postscript']
				&& ($version < $updateVersion
					|| ($version == $updateVersion
						&& $moduleStatus !== false
						&& in_array($moduleStatus, array('postscript', 'installed')) )))
			{
				$path = $updateInfo['path'] . 'post.php';
				$classname = $this->packageInfo->getFullName() . 'UpdatePostscript_' . $sanatizedVersionString;

				if(file_exists($path))
				{
					include($path);
					if(class_exists($classname, false))
					{
						$UpdatePreScript = new $classname();
						$UpdatePreScript->run();
					}
				}
			}

			$moduleStatus = false;
			$this->setVersion($updateInfo['version'], 'postscript');
		}
	}

	protected function setVersion(Version $version, $status, $module = true)
	{
		if($module)
		{
			$moduleRecord = new ObjectRelationshipMapper('modules');
			$moduleRecord->package = $this->packageInfo->getName();
			$moduleRecord->family = $this->packageInfo->getFamily();
		}else{
			$moduleRecord = new ObjectRelationshipMapper('schemaVersion');
			$moduleRecord->package = $this->packageInfo->getFullName();
		}

		$moduleRecord->select();
		$moduleRecord->status = $status;

		if(is_numeric($version->major))
			$moduleRecord->majorVersion = $version->major;

		if(is_numeric($version->minor))
			$moduleRecord->minorVersion = $version->minor;

		if(is_numeric($version->micro))
			$moduleRecord->microVersion = $version->micro;

		if(isset($version->releaseType))
			$moduleRecord->releaseType = $version->releaseType;

		if(isset($version->releaseVersion))
			$moduleRecord->releaseVersion = $version->releaseVersion;

		$moduleRecord->querySet('lastupdated', 'NOW()');

		if(!$moduleRecord->save())
		{
			throw new ModuleInstallerError('Unable to update package database with version information.');
		}else{
			return true;
		}
	}

	protected function addPermissions()
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

	protected function installModels()
	{
		$models = $this->packageInfo->getModels();
		foreach($models as $model)
		{
			// skip already registered models,
			if(ModelRegistry::getHandler($model['type']) !== false)
				continue;


			if(!class_exists($model['className']))
				throw new ModuleInstallerError('Unable to register model '
											   . $model['name'] . ' because class '
											   . $model['className'] . ' does not exist.');

			$class = new ReflectionClass($model['className']);
			if(($class->isAbstract() && !isset($model['type'])) || !isset($model['type']))
				continue;

			ModelRegistry::setHandler($model['type'], $this->packageInfo, $model['name']);
		}
	}
}


class ModuleInstallerError extends CoreError {}
class ModuleInstallerInfo extends CoreInfo {}
?>