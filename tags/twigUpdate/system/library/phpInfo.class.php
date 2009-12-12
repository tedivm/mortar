<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package Library
 * @subpackage Environment
 */

/**
 * This class allows developers to retrieve information about modules or extensions.
 *
 * @package Library
 * @subpackage Environment
 */
class phpInfo
{
	static protected $infoRegex = '#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s';

	static protected $allModules;
	static protected $moduleInfo;

	/**
	 * This function checks to see if a module is  present.
	 *
	 * @param string $extension
	 * @return bool
	 */
	static public function isLoaded($extension)
	{
		if(!isset(self::$allModules))
			self::loadEnvironmentInfo();

		return in_array($extension, self::$allModules);
	}

	/**
	 * This function returns the property of an extension. This roughly maps to the properties displayed by "phpinfo()".
	 *
	 * @param string $extension
	 * @param string $property
	 * @return mixed Returns false if the property is not present.
	 */
	static public function getExtensionProperty($extension, $property)
	{
		if(!isset(self::$moduleInfo))
			self::loadEnvironmentInfo();

		return (isset(self::$moduleInfo[$extension][$property])) ? self::$moduleInfo[$extension][$property] : false;
	}

	/**
	 * This function loads environmental information, such as the loaded modules and the module information from
	 * phpinfo.
	 *
	 */
	static protected function loadEnvironmentInfo()
	{
		$cache = new Cache('system', 'phpinfo');
		$phpinfo = $cache->getData();

		if($cache->isStale())
		{
			$time = microtime(true);
			ob_start();
			phpinfo();
			$phpInfoString = ob_get_clean();

			$loadedExtensions = get_loaded_extensions();
			$excludedExtentions = $loadedExtensions;

			$phpinfo = array();
			$currentKey = 'phpinfo';
			if(preg_match_all(self::$infoRegex, $phpInfoString, $matches, PREG_SET_ORDER))
			{
				foreach($matches as $match)
				{
					if(strlen($match[1]))
					{
						$phpinfo[$match[1]] = array();
						$currentKey = $match[1];

						if($key = array_search($currentKey, $excludedExtentions))
							unset($excludedExtentions[$key]);
					}else{

						if($match[2] == 'Directive')
							continue;

						if(!isset($match[3]))
							continue;

						$value = (isset($match[4])) ? $match[4] : $match[3];

						if($value == '<i>no value</i>')
							continue;

						$phpinfo[$currentKey][$match[2]] = $value;
					}
				}

				unset($phpinfo['PHP License']);
				unset($phpinfo['PHP Variables']);
				unset($phpinfo['Environment']);
				unset($phpinfo['Additional Modules']);
				unset($phpinfo['HTTP Headers Information']);
				unset($phpinfo['Apache Environment']);
			}
			$phpinfo['loadedExtension'] = $loadedExtensions;
			$cache->storeData($phpinfo);
		}

		self::$allModules = $phpinfo['loadedExtension'];
		unset($phpinfo['loadedExtension']);
		self::$moduleInfo = $phpinfo;
	}
}

?>