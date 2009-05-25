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
			$site = ActiveSite::getSite();
			$this->permissionObject = new Permissions($site->getLocation(), $user);
		}

		if(!$action)
			$action = staticHack(get_class($this), 'requiredPermission');

		$type = staticHack(get_class($this), 'requiredPermissionType');

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

abstract class FormActionBase extends ActionBase
{
	protected $formStatus = false;
	protected $form;
	protected $formName;

	public function logic()
	{
		$this->form = $this->getForm();

		if($this->form->checkSubmit())
		{
			$this->formStatus = ($this->processInput($this->form->getInputHandler()));
		}
	}

	protected function getForm()
	{
		$formName = $this->formName;
		$form = new $formName(get_class($this));

		return $form;
	}

	abstract protected function processInput($inputHandler);


	public function viewAdminForm()
	{
		$output = '';
		if($this->form->wasSubmitted())
		{
			if($this->formStatus)
			{
				$this->adminSuccess();
			}else{
				$this->adminError();
			}
		}else{

		}

		$info = InfoRegistry::getInstance();
		if(isset($info->Query['message']) && method_exists($this, 'adminMessage'))
		{
			$this->adminMessage($info->Query['message']);
		}


		$output .= $this->form->makeHtml();
		return $output;
	}

	protected function adminSuccess()
	{

	}

	protected function adminError()
	{

	}

	protected function adminMessage($messageId)
	{

	}
}

?>