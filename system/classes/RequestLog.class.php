<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage RequestWrapper
 */

/**
 * This class returns the arguments or query (get) values sent by the system
 *
 * @package System
 * @subpackage RequestWrapper
 */
class RequestLog
{
	/**
	 * In order to make it easier to offload the logging functions to another database we allow people to set two
	 * constants, REQUEST_LOG_DB and REQUEST_LOG_DB_READ. REQUEST_LOG_DB_READ falls back to REQUEST_LOG_DB and then to
	 * the default read database. REQUEST_LOG_DB falls back to the default write connection.
	 *
	 * @param string $name
	 * @return string
	 */
	static function getDatabase($name = 'read')
	{
		if($name == 'read')
		{
			return (defined('REQUEST_LOG_DB_READ'))
									? REQUEST_LOG_DB_READ
									: (defined('REQUEST_LOG_DB'))
										? REQUEST_LOG_DB
										: 'default_read_only';
		}else{
			return (defined('REQUEST_LOG_DB'))
									? REQUEST_LOG_DB
									: 'default';
		}
	}

	/**
	 * This function logs the request information to the database.
	 *
	 * @param int $userId
	 * @param int $siteId
	 * @param int $locationId
	 * @param string $module
	 * @param string $action
	 * @param string $ioHandler
	 * @param stringe $format
	 * @return bool
	 */
	static function logRequest($userId, $siteId, $locationId, $module, $action, $ioHandler, $format)
	{
		if(!$module)
			$module = 'none';

		if(!$locationId)
			$locationId = 0;

		$stmt = DatabaseConnection::getStatement(self::getDatabase('write'));
		$stmt->prepare('INSERT INTO requestLog (userId, siteId, location, module, action, ioHandler, format, accessTime)
									VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
		return $stmt->bindAndExecute('iiisssss', $userId, $siteId, $locationId,
					$module, $action, $ioHandler, $format, gmdate('Y-m-d H:i:s'));
	}

	/**
	 * This function logs an exception to the error log.
	 *
	 * @param BentoError $e
	 * @param int $severity
	 * @param null|string $type Defaults to the exception's class name.
	 * @return bool
	 */
	static function logError($e, $severity, $type = null)
	{
		$currentUrl = Query::getUrl();

		if(isset($type))
		{
			$errorType = $type;
			$message = get_class($e) . ': ' . $e->getMessage();
		}else{
			$errorType = get_class($e);
			$message = $e->getMessage();
		}

		$file = $e->getFile();
		$line = $e->getLine();
		$trace = $e->getTraceAsString();
		$code = $e->getCode();

		$stmt = DatabaseConnection::getStatement(self::getDatabase('write'));
		$stmt->prepare('INSERT INTO errorLog (errorType, severity, message, url, file, line, trace, accessTime)
									VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
		return $stmt->bindAndExecute('sissssss', $errorType, $severity, $message, (string) $currentUrl,
											$file, $line, $trace, gmdate('Y-m-d H:i:s'));
	}

}

?>