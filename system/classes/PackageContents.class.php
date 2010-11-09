<?php

class PackageContents
{
	static protected $moduleFolders = array('abstract' 	=> 'abstracts',
											'action' 	=> 'actions',
											'class' 	=> 'classes',
											'control' 	=> 'controls',
											'hook' 		=> 'hooks',
											'interface'	=> 'interfaces',
											'library' 	=> 'library',
											'model' 	=> 'models',
											'plugin' 	=> 'plugins');

	protected $path;
	protected $moduleName;
	protected $packageInfo;
	protected $isNamespace;

	static function getPackageContents(PackageInfo $packageInfo)
	{
		return new PackageContents($packageInfo);
	}

	protected function __construct(PackageInfo $packageInfo)
	{
		$this->packageInfo = $packageInfo;
		$this->path = $packageInfo->getPath();
		$this->isNamespace = $packageInfo->getMeta('autoload') == 'namespaces';
		if($this->isNamespace)
		{
			$module = $packageInfo->getName();
			$family = $packageInfo->getFamily();

			if($family != 'orphan')
				$module = $family . '\\' . $module;

			$this->moduleName = $module;
		}else{
			$this->moduleName = $packageInfo->getFullName();
		}
	}


	public function getClassNameByType($type, $shortname)
	{
		$type = strtolower($type);

		if(!isset(self::$moduleFolders[$type]))
			return false;

		$type = ucfirst($type);

		if($this->isNamespace)
		{
			if($type != 'Classes' && $type != 'Class')
				$shortname = $type . '\\' . $shortname;

			return 'Mortar\\' . $this->moduleName . '\\' . $shortname;
		}else{

			if($type != 'Classes' && $type != 'Class')
				$shortname = $type . $shortname;

			return $this->moduleName . $shortname;
		}
	}

	protected function loadPackageContents()
	{

	}

	protected function loadModels()
	{

	}

	protected function loadActions()
	{

	}

	protected function loadClasses()
	{

	}

	/**
	 * This is a utility method called by the load* functions to iterate through a directory for classes
	 *
	 * @param string $folder Package folder to look through
	 * @return array
	 */
	protected function loadFiles($folder)
	{
		if(!isset(self::$moduleFolders[$folder]))
			return false;

		$filePaths =  glob($this->path . self::$moduleFolders[$folder]  . '/*.class.php');

		$files = array();
		foreach($filePaths as $filename)
		{
			try {
				$fileInfo = array();
				$tmpArray = explode('/', $filename);
				$tmpArray = array_pop($tmpArray);
				$tmpArray = explode('.', $tmpArray);
				$fileClassName = array_shift($tmpArray);
				//explode, pop. explode. shift

				$fileInfo['classname'] = $this->getClassNameByType($folder, $fileClassName);
				$fileInfo['name'] = $fileClassName;
				$fileInfo['path'] = $filename;
				$files[$fileClassName] = $fileInfo;

			}catch(Exception $e){

			}
		}
		return $files;
	}



}

?>