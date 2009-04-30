<?php

class IOProcessorCli
{
	protected $responseCode;

	public function __construct()
	{
		$this->initialize();
	}

	public function initialize()
	{
		$this->start();
		$this->setEnvironment();
	}

	public function nextRequest()
	{
		return false;
	}

	public function output($output)
	{
		echo $output;
	}

	public function close()
	{

	}

	protected function setEnvironment()
	{
		$query = Query::getQuery();
		Form::disableXsfrProtection();
		Form::$userInput = 'stdin';

		if($query['disableCache'])
			Cache::$runtimeDisable = true;

		// poor windows people don't get this right now.
		$isRoot = (function_exists('posix_getuid') && posix_getuid() === 0);




		if(INSTALLMODE === true)
			return;

		$user = ActiveUser::getInstance();
		switch (true) {
			case ($query['username'] && $query['password']):
				$user->changeUser($query['username'], $query['password']);
				break;
			case ($query['username'] && $isRoot):
				$user->loadUserByName($query['username']);
				break;

			case ($isRoot):
				$user->loadUser(1);
				break;

			default:
				$user->loadUserByName('guest');
				break;
			}
	}

	protected function start()
	{

	}

	public function setStatusCode($code)
	{
		if(!is_numeric($code))
			throw new TypeMismatch(array('Numeric', $code));

		$this->responseCode = $code;
	}

}

?>