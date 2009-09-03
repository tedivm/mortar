<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage RequestWrapper
 */

/**
 * This class processes command line input and output
 *
 * @package System
 * @subpackage RequestWrapper
 */
class IOProcessorCli
{
	/**
	 * This is the response code, corresponding with http codes
	 *
	 * @var int
	 */
	protected $responseCode;

	/**
	 * This is a list of http headers to be sent out
	 *
	 * @access protected
	 * @var array
	 */
	protected $headers = array();

	/**
	 * On construct the initialize function is run
	 *
	 */
	public function __construct()
	{
		$this->initialize();
	}

	/**
	 * This function calls the functions to start the system and set the environment
	 *
	 */
	public function initialize()
	{
		$this->start();
		$this->setEnvironment();
	}

	/**
	 * This doesn't do much yet
	 *
	 * @return bool always false right now
	 */
	public function nextRequest()
	{
		return false;
	}

	/**
	 * This function outputs any values its sent to the system
	 *
	 * @param string $output
	 */
	public function output($output)
	{
		if(!is_numeric($output))
			$output .= PHP_EOL;

		echo $output;
	}

	/**
	 * This is called by the request handler during the last phase of execution
	 *
	 */
	public function close()
	{

	}

	/**
	 * This function sets the programming environment to match that of the system and method calling it
	 *
	 * @access protected
	 */
	protected function setEnvironment()
	{
		$query = Query::getQuery();
		Form::disableXsfrProtection();

		if($query['disableCache'])
			Cache::$runtimeDisable = true;

		// poor windows people don't get this right now.
		$isRoot = (function_exists('posix_getuid') && posix_getuid() === 0);

		if(INSTALLMODE === true)
			return;

		switch (true) {
			case ($query['username'] && $query['password']):
				ActiveUser::changeUserByNameAndPassword($query['username'], $query['password']);
				break;
			case ($query['username'] && $isRoot):
				ActiveUser::changeUserByName($query['username']);
				break;

			case ($isRoot):
				ActiveUser::changeUserById(1);
				break;

			default:
				ActiveUser::changeUserByName('guest');
				break;
			}
	}

	/**
	 * This function is called by the initialization function on load
	 *
	 * @access protected
	 */
	protected function start()
	{

	}

	/**
	 * This function is used to change the status code
	 *
	 * @param int $code
	 */
	public function setStatusCode($code)
	{
		if(!is_numeric($code))
			throw new TypeMismatch(array('Numeric', $code));

		$this->responseCode = $code;
	}

	/**
	 * Adds a new http header to be sent out
	 *
	 * @param string $name
	 * @param string $value
	 * @return bool
	 */
	public function addHeader($name, $value)
	{
		$name = (string) $name;
		$value = (string) $value;

		if(strpos($name, "\n") || strpos($value, "\n"))
			return false;

		$this->headers[$name] = $value;
		return true;
	}

}

?>