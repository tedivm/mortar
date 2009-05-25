<?php
/**
 * BentoBase
 *
 * @deprecated
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package		Library
 * @subpackage	Environment
 */

/**
 * This class is used to created Html structures that are organized and easy to read.
 *
 * @deprecated
 * @package		Library
 * @subpackage	Environment
 * @property-read ActiveUser $User
 * @property-read Config $Configuration
 * @property-read User $Site
 * @property-read Array $Input
 * @property-read Array $Query
 */
class InfoRegistry
{

	static $instance;

	private function __construct()
	{

	}

	public static function getInstance()
	{
		if(!isset(self::$instance)){
			$object = __CLASS__;
			self::$instance = new $object;
		}
		return self::$instance;
	}

	protected function __get($offset)
	{
		switch ($offset) {
			case 'User':
				return ActiveUser::getUser();
				break;
			case 'Configuration':
				return Config::getInstance();
				break;
			case 'Site':
				return ActiveSite::getSite();
				break;
			case 'Input':
				return Input::getInput();
				break;
			case 'Query':
				return Query::getQuery();
				break;
		//	case 'Runtime':
		//		$this->Runtime = RuntimeConfig::getInstance();
		//		break;
			default:
				return null;
				break;
		}
	}
}



?>