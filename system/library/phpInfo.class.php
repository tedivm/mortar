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
		$cache = CacheControl::getCache('system', 'phpinfo');
		$phpinfo = $cache->getData();

		if($cache->isStale())
		{
			$time = microtime(true);
			ob_start();
			phpinfo();
			$phpInfoString = ob_get_clean();

			$loadedExtensions = get_loaded_extensions();
			if(substr($phpInfoString, 0, 9) === 'phpinfo()')
			{
				$phpinfo = self::loadFromCli($phpInfoString);
			}else{
				$phpinfo = self::loadFromWeb($phpInfoString);
			}

			unset($phpinfo['Environment']);

			$phpinfo['loadedExtension'] = $loadedExtensions;
			$cache->storeData($phpinfo);
		}


		self::$allModules = $phpinfo['loadedExtension'];
		unset($phpinfo['loadedExtension']);
		self::$moduleInfo = $phpinfo;
	}

	static protected function loadFromWeb($phpinfo)
	{
		$infoArray = array();
		$currentKey = 'phpinfo';
		if(preg_match_all(self::$infoRegex, $phpinfo, $matches, PREG_SET_ORDER))
		{
			foreach($matches as $match)
			{
				if(strlen($match[1]))
				{
					$infoArray[$match[1]] = array();
					$currentKey = $match[1];
				}else{

					if($match[2] == 'Directive')
						continue;

					if(!isset($match[3]))
						continue;

					$value = $match[3];

					if($value == '<i>no value</i>')
						continue;

					$infoArray[$currentKey][$match[2]] = $value;
				}
			}

			unset($infoArray['PHP License']);
			unset($infoArray['PHP Variables']);
			unset($infoArray['Additional Modules']);
			unset($infoArray['HTTP Headers Information']);
			unset($infoArray['Apache Environment']);
		}

		return $infoArray;
	}

	static protected function loadFromCli($phpinfo)
	{
		$infoArray = array();
		$currentSection = 'phpinfo';

		$index = 0;
		$strlen = strlen($phpinfo);
		$lastLine = '';
		while($index < $strlen)
		{
			$nextNewLine = strpos($phpinfo, "\n", $index);
			if($nextNewLine !== false)
			{
				$currentLine = trim(substr($phpinfo, $index, $nextNewLine - $index));
				$index = $nextNewLine + 1;
			}else{
				$currentLine = trim(substr($phpinfo, $index));
				$index = $strlen;
			}

			switch(true)
			{
				case (strpos($currentLine, '=>') !== false):


					$pieces = explode('=>', $currentLine);
					$name = trim($pieces[0]);
					$value = trim($pieces[1]);

					if($name == 'Directive' || $value == 'no value')
						break;

					$infoArray[$currentSection][$name] = $value;
					break;

				// lines to skip
				case $currentLine == '':
				case (strpos($currentLine, 'Warning: phpinfo():') !== false):
				case (strpos($currentLine, 'Call Stack:') !== false):
					break;


				case ctype_alnum($currentLine):
					$currentSection = $currentLine;
					break;
			}
		}

		return $infoArray;
	}
}

?>