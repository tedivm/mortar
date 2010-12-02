<?php

class StashUtilities
{
	static function encoding($data)
	{
		if(is_bool($data))
			return 'bool';

		if(is_scalar($data))
			return 'none';

		return 'serialize';
	}

	static function encode($data)
	{
		switch(self::encoding($data))
		{
			case 'bool':
				return $data ? true : false;

			case 'serialize':
				return serialize($data);

			case 'none':
			default:
				return data;
		}
	}

	static function decode($data, $method)
	{
		switch($method)
		{
			case 'bool':
				return $data == 'true' ? true : false;

			case 'serialize':
				return unserialize($data);

			case 'none':
			default:
				return $data;
		}
	}

	/**
	 * This function is used to get around late static binding issues and other fun things in < php5.3
	 *
	 * @param string $className
	 * @param string $functionName
	 * @param mixed $arguments,...
	 */
	static function staticFunctionHack($className, $functionName)
	{
		$arguments = func_get_args();
		$className = array_shift($arguments);
		$functionName = array_shift($arguments);

		if(is_object($className))
			$className = get_class($className);

		/* This dirty hack is brought to you by php failing at oop */
		if(is_callable(array($className, $functionName)))
		{
			return call_user_func_array(array($className, $functionName), $arguments);
		}else{
			throw new StashError('static function ' . $functionName . ' not found in class ' . $className);
		}
	}

	/**
	 * This is used by handlers that require a path to store files or other data in when they haven't been passed a
	 * path. This directory is inside the systems temp folder and uses Stash's current location to keep its files
	 * seperate from other Stash libraries on the same machine. Additionally each handler class gets its own sub folder.
	 *
	 * @param StashHandler $handler
	 * @return string Path for Stash files
	 */
	static function getBaseDirectory(StashHandler $handler)
	{
		return sys_get_temp_dir() . 'stash_' . md5(dirname(__FILE__)) . '/' . get_class($handler) . '/';
	}

	static function deleteRecursive($file)
	{
		if(substr($file, 0, 1) !== '/')
			throw new StashError('deleteRecursive function requires an absolute path.');

		$badCalls = array('/', '/*', '/.', '/..');
		if(in_array($file, $badCalls))
			throw new StashError('deleteRecursive function does not like that call.');

		$filePath = rtrim($file, ' /');

		$directoryIt = new RecursiveDirectoryIterator($filePath);

		foreach(new RecursiveIteratorIterator($directoryIt, RecursiveIteratorIterator::CHILD_FIRST) as $file)
		{
			$filename = $file->getPathname();
			if($file->isDir())
			{
				$dirFiles = scandir($file->getPathname());
				if($dirFiles && count($dirFiles) == 2)
				{
					$filename = rtrim($filename, '/.');
					rmdir($filename);
				}
				unset($dirFiles);
				continue;
			}

			unlink($filename);
		}
	}
}

?>