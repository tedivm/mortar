<?php

class nModuleInstaller
{
	protected $package;
	protected $path;

	public function fullInstall()
	{
		try{
			if($this->checkRequirements())
			{
				// Because the dbConnect function pools connections, changing this 'default' connections settings
				// changes it for everything else that gets called, allowing us to easily roll back everything but
				// additions to the database structure (new tables, indexes, foreign keys).
				$db = DatabaseConnection::getConnection('default');
				$db->autocommit(false);

				try{
					$alreadyPresent = false;

					$stmt = DatabaseConnection::getStatement('default');
					$stmt->prepare('SELECT * FROM modules WHERE package = ?');
					$stmt->bindAndExecute('s', $this->package);

					if($stmt->num_rows > 0 && $row = $stmt->fetch_array())
					{
						$versionString = $this->versionToString($row);
						$version = $this->versionToInt($row);
						$moduleStatus = $row['status'];
						$id = $row['mod_id'];

						$alreadyPresent = true;

					}else{
						$verson = 0;
						$versionString = '0.0.0';
						$moduleStatus = false;
					}

					$stmt = DatabaseConnection::getStatement('default');
					$stmt->prepare('SELECT * FROM schemaVersion WHERE package = ?');
					$stmt->bindAndExecute('s', $this->package);

					if($stmt->num_rows > 0 && $row = $stmt->fetch_array())
					{
						$dbVersionString = $this->versionToString($row);
						$dbVersion = $this->versionToInt($row);
						$schemaStatus = $row['status'];
						$alreadyPresent = true;
					}else{
						$dbVersion = 0;
						$schemaStatus = false;
					}


					if($alreadyPresent)
					{

						$lowestVersion = ($dbVersion <= $version) ? $dbVersion : $version;
						$updates = $this->getUpdateList($lowestVersion);

						foreach($updates as $updateVersion => $updateInfo)
						{
							$sanatizedVersionString = str_replace(array('.', '-'), '_', $updateInfo['folder']);

							if(($version < $updateVersion) && ($updateInfo['prescript'] && $moduleStatus !== false))
							{
								$path = $updateInfo['path'] . 'pre.php';
								$classname = $this->package . 'UpdatePreScript' . $sanatizedVersionString;

								if(file_exists($path))
								{
									inculde($path);
									if(class_exists($classname, false))
									{
										$UpdatePreScript = new $classname();
										$UpdatePreScript->run();
									}
								}
							}


							$this->setModuleVersion($updateInfo['version'], 'prescript');

							if($dbVersion < $updateVersion && $schemaStatus != 'full')
							{
								if($updateInfo['sqlstructure'] && $schemaStatus != 'structure')
								{
									$path = $updateInfo['path'] . 'structure.sql';
									$db->runFile($path);
								}

								$this->setDatabaseVersion($updateInfo['version'], 'structure');

								if($updateInfo['sqldata'])
								{
									$path = $updateInfo['path'] . 'data.sql';
									$db->runFile($path);
								}

								$this->setDatabaseVersion($updateInfo['version'], 'full');
							}
							$schemaStatus = false;

							if(($version < $updateVersion)
							   && ($updateInfo['postscript']
								   && $moduleStatus != 'postscript' && $moduleStatus != 'installed'))
							{
								$path = $updateInfo['path'] . 'post.php';
								$classname = $this->package . 'UpdatePostScript' . $sanatizedVersionString;

								if(file_exists($path))
								{
									inculde($path);
									if(class_exists($classname, false))
									{
										$UpdatePreScript = new $classname();
										$UpdatePreScript->run();
									}
								}
							}

							$moduleStatus = false;
							$this->setModuleVersion($updateInfo['version'], 'postscript');
						}

					}else{

						// install database
						// run script
					}

					$this->addPermissions();
					$this->installModels();

					$this->setModuleVersion($updateInfo['version'], 'installed');

					$db->commit();
					$db->autocommit(true);
					return true;
				}catch(Exception $e){
					$db->rollback();	// problem, this could erase the status, which means the database structure
										// would be set up but the system wouldn't know
					$db->autocommit(true);
					throw new ModuleInstallerError('Unable to install module ' . $this->package . ', rolling back database changes.');
				}

			}else{
				// some sort of way to show the error
			}
		}catch(Exception $e){
			return false;
		}
		return true;
	}

	protected function getUpdateList($currentVersion)
	{
		$path = $this->path . 'updates/';;
		$updatePaths = glob($path . '*', GLOB_ONLYDIR);
		$updatePackages = array();
		foreach($updatePaths as $folder)
		{
			$realFolder = substr($folder, strrpos($folder, '/'));
			$realFolder = trim($realFolder, '/');
			$versionChunks = explode('.', $realFolder);
			$versionArray = array();
			$versionArray['versionMajor'] = isset($versionChunks[0]) ? $versionChunks[0] : 0;
			$versionArray['versionMinor'] = isset($versionChunks[1]) ? $versionChunks[1] : 0;
			$versionArray['versionMicro'] = isset($versionChunks[2]) ? $versionChunks[2] : 0;
			$version = $this->versionToInt($versionArray());

			// skip updates we aren't going to use
			// we grab the current version in case we're continuing a partial update
			if($version < $currentVersion)
				continue;

			$updatePackages[$version]['path'] = $folder;

			$updatePackages[$version]['sqlstructure'] = (bool) file_exists($folder . 'structure.sql');
			$updatePackages[$version]['sqldata'] = (bool) file_exists($folder . 'data.sql');
			$updatePackages[$version]['prescript'] = (bool) file_exists($folder . 'pre.php');
			$updatePackages[$version]['postscript'] = (bool) file_exists($folder . 'post.php');
			$updatePackages[$version]['folder'] = $realFolder;
			$updatePackages[$version]['path'] = $folder;

			$updatePackages[$version]['version']['major'] = $versionArray['versionMajor'];
			$updatePackages[$version]['version']['minor'] = $versionArray['versionMinor'];
			$updatePackages[$version]['version']['micro'] = $versionArray['versionMicro'];
		}



		return $updatePackages;

	}

	protected function setDatabaseVersion(array $version, $status)
	{
		// if we've just updated the structure we want to make sure we record that change no matter what, since
		// rolling back the transaction will not roll back the table changes. Thus we need a brand new connection
		// that has autocommit on.
		if($status == 'structure')
		{
			$db = DatabaseConnection::getConnection('default', false);
		}else{
			$db = DatabaseConnection::getConnection('default');
		}

		$stmt = $db->stmt_init();
		$stmt->prepare('REPLACE INTO schemeVersion
							(package, lastUpdated, majorVersion, minorVersion, microVersion, status)
							VALUES (?, NOW(), ?, ?, ?, ?)');
		$stmt->bindAndExecute('siiis', $this->package,
								$version['major'], $version['minor'], $version['micro'], $status);

		if($status == 'structure')
			$db->close();

		return true;
	}

	protected function setModuleVersion(array $version, $status)
	{
		$moduleRecord = new ObjectRelationshipMapper('modules');
		$moduleRecord->package = $this->package;
		$moduleRecord->select();
		$moduleRecord->status = $status;


		if(is_numeric($version['major']))
			$moduleRecord->majorVersion = $version['major'];
		if(is_numeric($version['minor']))
			$moduleRecord->minorVersion = $version['minor'];
		if(is_numeric($version['micro']))
			$moduleRecord->microVersion = $version['micro'];

		if(isset($version['type']))
			$moduleRecord->releaseType = $version['type'];

		if(isset($version['tVersion']))
			$moduleRecord->releaseVersion = $version['tVersion'];

		$moduleRecord->querySet('lastupdated', 'NOW()');

		if(!$moduleRecord->save())
		{
			throw new ModuleInstallerError('Unable to update package database with version information.');
		}else{
			return true;
		}
	}


	protected function versionToInt(array $versionPieces)
	{
		if(!isset($row['majorVersion']))
			$row['majorVersion'] = 0;

		$dbVersion = sprintf('%04s', $row['majorVersion']);

		if(!isset($row['minorVersion']))
			$row['minorVersion'] = 0;

		$dbVersion .= sprintf('%04s', $row['minorVersion']);

		if(!isset($row['microVersion']))
			$row['microVersion'] = 0;

		$dbVersion .= sprintf('%04s', $row['microVersion']);

		return (int) $version;
	}

	protected function versionToString(array $versionPieces)
	{
		$dbVersionString = isset($row['majorVersion']) ? $row['majorVersion'] : '0';
		$dbVersionString .= '.';
		$dbVersionString .= isset($row['minorVersion']) ? $row['minorVersion'] : '0';
		$dbVersionString .= '.';
		$dbVersionString .= isset($row['microVersion']) ? $row['microVersion'] : '0';

		return $dbVersionString;
	}




}


class ModuleInstallerError extends CoreError {}
?>