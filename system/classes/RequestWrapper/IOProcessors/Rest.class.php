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
 * This class processes REST based (http) input and output
 *
 * @package System
 * @subpackage RequestWrapper
 */
class IOProcessorRest extends IOProcessorHttp
{
	/**
	 * These methods can be triggered using a post request with the input "methodOverride" set
	 *
	 * @static
	 * @var array
	 */
	static public $postOverrides = array('put', 'delete', 'options');

	/**
	 * This function sets the programming environment to match that of the system and method calling it
	 *
	 * @access protected
	 */
	protected function setEnvironment()
	{
		$query = Query::getQuery();
		Form::disableXsfrProtection();

		switch(true)
		{
			case (strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false):
				$query['format'] = 'Json';
				break;

			case (strpos($_SERVER['HTTP_ACCEPT'], 'rss') !== false):
				$query['format'] = 'Rss';
				break;

			case (strpos($_SERVER['HTTP_ACCEPT'], 'html') !== false):
				$query['format'] = 'Html';
				break;

			case (strpos($_SERVER['HTTP_ACCEPT'], 'xml') !== false):
			default:
				$query['format'] = 'Xml';
				break;
		}

		$method = strtolower($_SERVER['REQUEST_METHOD']);

		if($method == 'post')
		{
			Form::$userInput = Post::getInstance();

			// This will allow clients that can't access the put/delete methods
			// (such as forms) to use a post override
			$post = Post::getInstance();
			$override = strtolower($post['methodOverride']);

			if(in_array($override, self::$postOverrides))
				$method = $override;

		}elseif($method == 'put'){
			Form::$userInput = Put::getInstance();
		}

		switch($method)
		{
			default:
			case 'head':
			case 'get':
				$query['action'] = 'read';
				break;

			case 'post':
				$query['action'] = 'add';
				break;

			case 'put':
				$query['action'] = 'edit';
				break;

			case 'delete':
				$query['action'] = 'delete';
				break;

			case 'options':
				$query['action'] = 'options';
				break;
		}
	}


}

?>