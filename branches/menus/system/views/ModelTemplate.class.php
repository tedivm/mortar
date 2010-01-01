<?php

class ViewModelTemplate extends ViewThemeTemplate
{
	protected $twigLoader = 'TwigIntegrationModelLoader';
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

		$model = $this->model;
		$module = $model->getModule();
		$package = new PackageInfo($module);
		$packagePath = $package->getPath() . 'templates/';

		$parentPaths['module'] = $packagePath;
		return $parentPaths;
	}
}

?>