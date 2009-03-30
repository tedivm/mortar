<?php

abstract class ModelActionBase implements ActionInterface
{
	protected $model;
	protected $ioHandler;

	protected $permissionObject;

	protected $cacheExpirationOffset;

	public static $requiredPermission;

	public function __construct($identifier, $handler)
	{
		if(!($identifier instanceof Model))
			throw new TypeMismatch(array('Model', $identifier));

		$this->model = $identifier;
		$this->ioHandler = $handler;
	}


	public function start()
	{
		if($this->checkAuth() !== true)
			throw new AuthenticationError();

		if(method_exists($this, 'logic'))
			$this->logic();


		if(method_exists($this, 'setHeaders'))
			$this->setHeaders();
	}

	protected function setHeaders()
	{
		$location = $this->model->getLocation();
		//$location = new Location();

		$modifiedDate = strtotime($location->getLastModified());
		$creationDate = strtotime($location->getCreationDate());

		$this->ioHandler->addHeader('Last-Modified', gmdate('D, d M y H:i:s T', $modifiedDate));
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