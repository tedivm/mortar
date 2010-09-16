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

		if(is_array($name)) {
			$names = array();
			foreach($name as $n) {
				$names[] = 'models/' . $type . '/' . $n;
			}
			$this->name = $names;
		} else {
			$this->name = 'models/' . $type . '/' . $name;
		}
	}

	protected function getThemePaths()
	{
		$parentPaths = parent::getThemePaths();

		$model = $this->model;
		$module = $model->getModule();

		$package = PackageInfo::loadById($module);
		$packagePath = $package->getPath() . 'templates/';

		$parentPaths['module'] = $packagePath;
		return $parentPaths;
	}

	protected function getTwigLoader($basePath)
	{
		$loader = parent::getTwigLoader($basePath);
		$loader->useModel($this->model);
		return $loader;
	}

}

?>