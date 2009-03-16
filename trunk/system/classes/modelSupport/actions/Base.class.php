<?php

abstract class ModelActionBase
{
	protected $model;
	protected $requestHandler;

	public function __construct($identifier, $handler)
	{
		if(!($identifier instanceof Model))
			throw new TypeMismatch(array('Model', $identifier));

		$this->model = $identifier;
		$this->requestHandler = $handler;
	}


	abstract public function start();


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
}

?>