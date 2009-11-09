<?php

class ViewModelTemplate extends ViewThemeTemplate
{
	protected $twigLoader = 'ViewModelTemplateTwigLoader';
	protected $model;

	public function __construct(Theme $theme, Model $model, $name)
	{
		$this->theme = $theme;
		$this->model = $model;

		$type = $model->getType();

		$this->name = 'models/' . $type . '/' . $name;
	}

	protected function getThemePaths()
	{
		$parentPaths = parent::getThemePaths();

		$model = new Model();
		$module = $model->getModule();
		$package = new PackageInfo($module);
		$packagePath = $package->getPath();

		$parentPaths['module'] = $packagePath;
		return $parentPaths;
	}
}

class ViewModelTemplateTwigLoader extends ViewTemplateTwigLoader
{
	protected $__runtimeLoopProtection = false;

	protected function loadExtraClasses($name)
	{
		if($this->__runtimeLoopProtection)
			return array();
		$this->__runtimeLoopProtection = true;


		$tmp = substr($name, 7);
		$baseName = substr($tmp, strpos($name, '/'));

		$baseTemplates = $this->loadTemplateSet('models/base/' . $baseName);
		$this->__runtimeLoopProtection = false;
		return $baseTemplates;
	}
}

?>