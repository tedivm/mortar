<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage UserInputs
 */

/**
 * This class returns the arguments or query (get) values sent by the system
 *
 * @package System
 * @subpackage UserInputs
 */
class Query
{
	/**
	 * This is a copy of the array used in order to save changes
	 *
	 * @access protected
	 * @static
	 * @var FilteredArray
	 */
	static protected $query;

	static public function setQuery($query)
	{
		if(is_array($query))
			self::$query = new FilteredArray($query);
	}

	/**
	 * This function returns the current Query (Arguments or Get values)
	 *
	 * @static
	 * @return FilteredArray
	 */
	static function getQuery()
	{
		if(!self::$query)
		{
			$type = (defined('STDIN') || !isset($_SERVER['REQUEST_METHOD'])) ? 'Argv' : 'Get';

			if(!class_exists($type, false))
			{
				if(!(include('inputHandlers/' . $type . '.class.php')))
					throw new QueryError('Unable to load input handler ' . $type);
			}

			$input = staticFunctionHack($type, 'getArray');

			$urlRouter = new UrlReader();
			$inputArray = $urlRouter->readUrl($input);


			if($type == 'Argv')
			{
				if(isset($inputArray['cron']))
				{
					$inputArray['ioType'] = 'Cron';
				}else{
					$inputArray['ioType'] = 'Cli';
				}

			}elseif(!isset($inputArray['ioType'])){
				$inputArray['ioType'] = 'Http';
			}

			if(!isset($inputArray['format']))
			{
				switch (strtolower($inputArray['ioType']))
				{
					case 'rest':
						$inputArray['format'] = 'Xml';
						break;

					case 'cli':
						$inputArray['format'] = 'Text';
						break;

					default:
					case 'http':
						$inputArray['format'] = 'Html';
						break;
				}
			}

			self::$query = new FilteredArray($inputArray);
		}

		return self::$query;
	}

	/**
	 * Returns a Url using the current arguments (think $_SERVER['PHP_SELF'] without the XSS)
	 *
	 * @static
	 * @return Url
	 */
	static function getUrl()
	{
		$query = self::getQuery();
		$attributes = $query->getArrayCopy();

		$url = new Url();

		if(isset($attributes['p']))
			unset($attributes['p']);

		if(isset($attributes['format']) && $attributes['format'] == 'Html')
			unset($attributes['format']);

		if(!isset($attributes['location']))
		{
			$site = ActiveSite::getSite();
			if($site)
				$url->location = $site->getLocation();
		}

		foreach($attributes as $name => $value)
			$url->$name = $value;

		return $url;
	}

}

class QueryError extends CoreError {}
?>