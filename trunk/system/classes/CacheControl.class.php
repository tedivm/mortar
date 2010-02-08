<?php

class CacheControl
{
	static function getCache()
	{
		$args = func_get_args();
		$cache = new Cache($args);
		return $cache;
	}

	static function clearCache()
	{
		$args = func_get_args();
		Cache::clear($args);
	}

	static function purgeCache()
	{
		Cache::purge();
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

?>