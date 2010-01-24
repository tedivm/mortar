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

					$stmt = DatabaseConnection::getStatement('default');
					$stmt->prepare('SELECT * FROM modules WHERE package = ?');
					$stmt->bindAndExecute('s', $this->package);

					if($stmt->num_rows > 0 && $row = $stmt->fetch_array())
					{
						$versionString = $this->versionToString($row);
						$version = $this->versionToInt($row);
						$status = $row['status'];
						$id = $row['mod_id'];

						$alreadyPresent = true;

					}else{
						$verson = 0;
						$versionString = '0.0.0';
					}

					$stmt = DatabaseConnection::getStatement('default');
					$stmt->prepare('SELECT * FROM schemaVersion WHERE package = ?');
					$stmt->bindAndExecute('s', $this->package);

					if($stmt->num_rows > 0 && $row = $stmt->fetch_array())
					{
						$dbVersionString = $this->versionToString($row);
						$dbVersion = $this->versionToInt($row);
						$alreadyPresent = true;
					}else{
						$dbVersion = 0;
					}


					if($alreadyPresent)
					{

						$lowestVersion = ($dbVersion <= $version) ? $dbVersion : $version;
						$updates = $this->getUpdateList($lowestVersion);

						foreach($updates as $updateVersion => $updateInfo)
						{

							$sanatizedVersionString = str_replace(array(',', '-'), '_', $updateInfo['folder']);

							if($version < $updateVersion)
							{
								if($updateInfo['prescript'])
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
							}

							if($dbVersion < $updateVersion)
							{
								if($updateInfo['sqlstructure'])
								{
									$path = $updateInfo['path'] . 'structure.sql';
									$db->runFile($path);
								}

								if($updateInfo['sqldata'])
								{
									$path = $updateInfo['path'] . 'data.sql';
									$db->runFile($path);
								}

								/**
								 * @todo update database version
								 */
							}

							if($version < $updateVersion)
							{
								if($updateInfo['postscript'])
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
							}

							/**
							 * @todo update module version
							 */
						}

					}else{

						// install database
						// run script
					}

					$this->addPermissions();
					$this->installModels();

					/**
					* @todo update module version to current
					*/

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
			if($version <= $currentVersion)
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



?>