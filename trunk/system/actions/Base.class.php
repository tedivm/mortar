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
	public static $requiredPermissionType;

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
			$user = ActiveUser::getUser();

			if($site = ActiveSite::getSite())
			{
				$this->permissionObject = new Permissions($site->getLocation(), $user); // 1 == Root
			}else{
				$this->permissionObject = new Permissions(1, $user); // 1 == Root
			}

//			$this->permissionObject = new Permissions(1, $user); // 1 == Root
		}

		if(!$action)
			$action = staticHack(get_class($this), 'requiredPermission');

		$type = staticHack(get_class($this), 'requiredPermissionType');

		if(!$type)
			$type = 'Base';

		return $this->permissionObject->isAllowed($action, $type);
	}

	public function getName()
	{
		return $this->actionName;
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