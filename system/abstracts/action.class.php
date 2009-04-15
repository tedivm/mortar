<?php

abstract class ActionBase implements ActionInterface
{
	protected $argument;
	protected $ioHandler;
	protected $package;
	protected $actionName;

	protected $permissionObject;

	protected $cacheExpirationOffset;

	public static $requiredPermission;

	public function __construct($argument, $handler)
	{
		$this->argument = $argument;
		$this->ioHandler = $handler;

		$namingInfo = explode('Action', get_class($this));
		$this->actionName = array_pop($namingInfo);
		$this->package = array_shift($namingInfo);
	}

	public function start()
	{
		if($this->checkAuth() !== true)
			throw new AuthenticationError();

		if(method_exists($this, 'logic'))
			$this->logic();
	}

	public function checkAuth($action = NULL)
	{
		if(!isset($this->permissionObject))
		{
			$user = ActiveUser::getInstance();
			$site = ActiveSite::getSite();
			$this->permissionObject = new Permissions($site->getLocation(), $user);
		}

		if(!$action)
			$action = staticHack(get_class($this), 'requiredPermission');

		$type = staticHack(get_class($this), 'permissionType');

		if(!$type)
			$type = 'Base';

		return $this->permissionObject->isAllowed($action, $type);
	}

	/*
	public function viewAdmin()
	{

	}

	public function viewHtml()
	{

	}

	public function viewXml()
	{

	}

	public function viewJson()
	{

	}
	*/
}

?>