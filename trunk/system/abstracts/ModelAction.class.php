<?php

abstract class ModelAction implements ActionInterface
{
	protected $modelClass;
	protected $moduleType;
	protected $model;

	static $requiredPermission = 'Read';


	public function __construct($identifier)
	{
		if(!is_int($identifier))
			throw new TypeMismatch(array('Integer', $identifier));

		$location = new Location($identifier);

		if($location->getResource() != $this->moduleType)
			throw new BentoError('You can not use this action with that type of model');

		$className = $this->modelClass;
		$model = new $className($identifier);
		$this->model = $model;
	}

	public function start()
	{
		if(method_exists($this, 'logic'))
			$this->logic();
	}





	public function checkAuth($action = NULL)
	{
		if(is_null($action))
			$action = $this->getRequiredPermission();

		return $this->model->isAllowed($action);
	}

	protected function getRequiredPermission()
	{
		return self::$requiredPermission;
	}
}


?>