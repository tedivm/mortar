<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ModelSupport
 */

/**
 * This class tracks the handlers for all of the models in the system
 *
 * @abstract
 * @package System
 * @subpackage ModelSupport
 */
abstract class ModelActionBase implements ActionInterface
{
	protected $model;
	protected $ioHandler;
	protected $type;

	protected $permissionObject;

	protected $cacheExpirationOffset;

	public static $requiredPermission = 'Read';

	public function __construct($identifier, $handler)
	{
		if(!($identifier instanceof Model))
			throw new TypeMismatch(array('Model', $identifier));


		$this->model = $identifier;
		$this->ioHandler = $handler;
		$this->type = $this->model->getType();
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


		if(method_exists($this, 'setHeaders'))
			$this->setHeaders();
	}

	protected function setHeaders()
	{
		$location = $this->model->getLocation();
		$modifiedDate = strtotime($location->getLastModified());
		$this->ioHandler->addHeader('Last-Modified', gmdate('D, d M y H:i:s T', $modifiedDate));
	}


	public function checkAuth($action = NULL)
	{
		if(!isset($this->permissionObject))
			$this->setPermissionObject();

		if(!$action)
			$action = staticHack(get_class($this), 'requiredPermission');


		return $this->permissionObject->isAllowed($action, $this->model->getType());
	}

	protected function setPermissionObject()
	{
		$user = ActiveUser::getInstance();
		$this->permissionObject = new Permissions($this->model->getLocation(), $user);
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