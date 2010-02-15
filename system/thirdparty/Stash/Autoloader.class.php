<?php

class StashAutoloader
{
	static protected $classes = array(
										'Handler' => 'Handler.class.php',
										'StashError' => 'Error.class.php',
										'StashWarning' => 'Warning.class.php',
										'StashUtilities' => 'Utilities.class.php',
										'Stash' => 'Stash.class.php',

										'StashApc' => 'handlers/Apc.class.php',
										'StashXcache' => 'handlers/Xcache.class.php',
										'StashSqlite' => 'handlers/Sqlite.class.php',
										'StashFileSystem' => 'handlers/FileSystem.class.php',
										'StashSqliteOneFile' => 'handlers/SqliteOneFile.class.php',
										'StashMultiHandler' => 'handlers/MultiHandler.class.php',
									);


	static function autoload($classname)
	{
		if(!isset(self::$classes[$classname]))
			return false;

		$currentDir = dirname(__file__);

		if(!file_exists($currentDir . self::$classes[$classname]))
			return false;

		include($currentDir . self::$classes[$classname]);
	}

	static function loadAll()
	{
		$currentDir = dirname(__file__) . '/';

		foreach(self::$classes as $classname => $path)
		{
			if(class_exists($classname, false) || !file_exists($currentDir . $path))
				continue;

			include($currentDir . $path);
		}
	}
}

?>