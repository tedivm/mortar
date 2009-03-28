<?php

abstract class ModelActionBase implements ActionInterface
{
	protected $model;
	protected $requestHandler;

	protected $permissionObject;

	public static $requiredPermission;

	public function __construct($identifier, $handler)
	{
		if(!($identifier instanceof Model))
			throw new TypeMismatch(array('Model', $identifier));

		$this->model = $identifier;
		$this->requestHandler = $handler;
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
			$this->permissionObject = new Permissions($this->model->getLocation(), $user);
		}

		if(!$action)
			$action = staticHack(get_class($this), 'requiredPermission');

		return $this->permissionObject->isAllowed($action, $this->model->getType());
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