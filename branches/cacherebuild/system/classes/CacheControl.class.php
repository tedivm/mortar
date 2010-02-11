<?php

class CacheControl
{
	static protected $cacheHandler;

	static function disableCache($flag = true)
	{
		Stash::$runtimeDisable = (bool) $flag;
	}

	static function getUnprimedCache()
	{
		if(!isset(self::$cacheHandler))
			self::setCacheHandler();

		$cache = new Stash(self::$cacheHandler);
		return $cache;
	}

	static function getCache()
	{
		$args = func_get_args();
		if(count($args) == 1 && is_array($args[0]))
			$args = $args[0];
		$cache = self::getUnprimedCache();
		$cache->setupKey($args);
		return $cache;
	}
	
	static protected function setCacheHandler()
	{
		$handlers = Stash::getHandlers();

		$config = Config::getInstance();
		$handlerType = (isset($config['system']['cacheHandler'])
							&& isset($handlers[$config['system']['cacheHandler']]))
									? $config['system']['cacheHandler']
									: 'FileSystem';

		$handlerClass = $handlers[$handlerType];

		if(!class_exists($handlerClass))
		{
			Stash::$runtimeDisable = true;
			throw new CacheError('Unable to load cache handler ' . $handlerType);
		}

		$handler = new $handlerClass(array('path' => $config['path']['temp'] . 'cache'));
		self::$cacheHandler = $handler;
	}

	static function clearCache()
	{
		$args = func_get_args();
		$numArgs = count($args);

		$cache = self::getUnprimedCache();

		if($numArgs === 0)
			return $cache->clear();

		if($numArgs == 1 && is_array($args[0]))
			$args = $args[0];

		return $cache->clear($args);
	}

	static function purgeCache()
	{
		$cache = self::getUnprimedCache();
		return $cache->purge();
	}

	static function getCacheHandlers()
	{
		return Stash::getHandlers();
	}

	static function getOutputCache($md5, $encoding)
	{
		$config = Config::getInstance();
		$cachePath = $config['path']['temp'] . 'outputCompression/' . $md5 . '.' . $encoding;

		if(file_exists($cachePath))
		{
			$storedOutput = file_get_contents($cachePath);
			return $storedOutput;
		}else{
			return false;
		}
	}

	static function saveOutputCache($md5, $output, $encoding)
	{
		$config = Config::getInstance();
		$cachePath = $config['path']['temp'] . 'outputCompression/' . $md5 . '.' . $encoding;

		$dir = dirname($cachePath);
		if(!is_dir($dir))
		{
			if(!mkdir($dir, 0700, true))
				return $output;
		}

		return file_put_contents($cachePath, $output);
	}

	static function clearOutputCache()
	{
		$config = Config::getInstance();
		$path = $config['path']['temp'] . 'outputCompression/';

		if(is_dir($path))
			return FileSystem::deleteRecursive($path);

		return true;
	}
}

class CacheError extends CoreError {}
?>