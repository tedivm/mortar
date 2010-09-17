<?php

class ModuleFamily
{
	static function listFamilies()
	{
		$cache = CacheControl::getCache('modules', 'families', 'list');
		$families = $cache->getData();

		if($cache->isStale())
		{
			$config = Config::getInstance();
			$path = $config['path']['modules'];
			$childrenDirectories = glob(FileSystem::normalizeTrailingSlash($path) . '*', GLOB_ONLYDIR);

			$families = array();
			foreach($childrenDirectories as $directory)
			{
				$directory = FileSystem::normalizeTrailingSlash($directory);
				if(file_exists($directory . 'package.ini'))
					continue;

				$directory = trim($directory, '/\\');
				$families[] = substr($directory, strrpos($directory, '/') + 1);
			}

			$cache->storeData($families);
		}

		return $families;
	}

	static function listModules($family, $byName = true)
	{
		if(!self::familyExists($family))
		{
			new ModuleFamilyNotice('Unable to list modules for nonexistant family.');
			return false;
		}

		$cache = CacheControl::getCache('modules', 'families', $family, 'modules');
		$families = $cache->getData();

		if($cache->isStale)
		{
			$config = Config::getInstance();
			$path = $config['path']['modules'] . $family;
			$childrenDirectories = glob(FileSystem::normalizeTrailingSlash($path) . '*', GLOB_ONLYDIR);

			$modules = array();
			foreach($childrenDirectories as $directory)
			{
				$directory = FileSystem::normalizeTrailingSlash($directory);
				if(file_exists($directory . 'package.ini'))
					continue;

				$directory = trim($directory, '/\\');
				$modules[] = substr($directory, strrpos($directory, '/'));
			}

			$cache->storeData($modules);
		}

		if(!$byName)
		{
			$cache = CacheControl::getCache('modules', 'families', $family, 'modules', 'filteredById');
			$filteredModules = $cache->getData();

			if($cache->isStale())
			{
				$filteredModules = self::filterModules($family, $modules);
				$cache->storeData = $filteredModules();
			}
			$modules = $filteredModules;
		}


		return $modules;
	}

	static protected function filterModules($family, $modules)
	{
		$filteredModules = array();
		foreach($modules as $module)
		{
			$packageInfo = PackageInfo::loadByName($family, $module);

			if(!($id = $packageInfo->getId()))
				$filteredModules[0][] = $module;
			$filteredModules[$id] = $module;
		}

		return $filteredModules;
	}

	static function familyExists($family)
	{
		$families = self::listFamilies();
		return in_array($family, $families);
	}
}

class ModuleFamilyNotice extends CoreNotice {}

?>