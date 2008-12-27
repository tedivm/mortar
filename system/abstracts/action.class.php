<?php

//spl_autoload_call('ActionInterface');

abstract class Action extends ModuleBase implements ActionInterface
{
	static $requiredPermission = 'Read';
	protected $permissions;
	protected $engineHelper;
	protected $actionName;

	public function __construct($modId)
	{
		$this->moduleId = $modId;
		$this->loadSettings();
		$this->startUp();
	}

	protected function startUp()
	{
		$runtime = RuntimeConfig::getInstance();

		$pathToEngineHelper = $config['path']['engines'] . $runtime['engine'] . '.engineHelper.php';

		$helperClassName = $runtime['engine'] . 'Helper';

		if(!class_exists($helperClassName, false) && file_exists($pathToEngineHelper))
			include($pathToEngineHelper);

		if(class_exists($helperClassName, false))
			$this->engineHelper = new $helperClassName();

		$this->actionName = array_pop(explode('Action', get_class($this)));

		if(method_exists($this, 'logic'))
			$this->logic();

	}

	public function checkAuth($action = false)
	{
		$checkAction = ($action) ? $action : staticHack($this, 'requiredPermission');
		return $this->isAllowed($checkAction); // retarded hack until php 5.3 comes out
	}

    public function isAllowed($action)
    {
        if(!$this->permissions)
        {
            $user = ActiveUser::get_instance();
            $this->permissions = new Permissions($this->location, $user->getId());
        }

    	return $this->permissions->isAllowed($action);
    }

    protected function linkToSelf()
    {

    	$url = new Url();
    	$url->property('module', $this->moduleId);
    	$url->property('action', $this->actionName);
    	return $url;
    }



	/*

	Example displays

	public function viewAdmin();

	public function viewHtml();

	public function viewXml();

	public function viewRss();

	*/


}

abstract class PackageAction extends Action
{

	public function __construct($package)
	{
		$this->package = $package;
		$this->loadSettings();
		$this->startUp();
	}


	public function isAllowed($action)
	{
		$user = ActiveUser::get_instance();
		$packageInfo = new PackageInfo($this->package);
		return $packageInfo->checkAuth($action);
	}

	protected function linkToSelf()
	{
		$url = new Url();
		$url->property('package', $this->package);
    	$url->property('action', $this->actionName);
		return $url;
	}

}

?>