<?php

class TwigIntegrationModelLoader extends TwigIntegrationThemeLoader
{
	protected $__runtimeLoopProtection = false;
	protected $model;

	protected function loadExtraClasses($name)
	{
		if($this->__runtimeLoopProtection)
			return array();
		$this->__runtimeLoopProtection = true;

		$tmp = substr($name, 7);
		$baseName = substr($tmp, strpos($tmp, '/'));
		$baseTemplates = $this->loadTemplateSet('models/base' . $baseName);

		$descent = $this->model->getDescent();

		if(isset($descent) && is_array($descent))
		{
			foreach($descent as $ancestor)
			{
				$ancestorTemplates = $this->loadTemplateSet('models/' . $ancestor . $baseName);
				if(isset($extraTemplates))
				{
					if(isset($ancestorTemplates))
					{
						$extraTemplates = array_merge($extraTemplates, $ancestorTemplates);
					}
				} else {
					$extraTemplates = $ancestorTemplates;
				}
			}
		}

		if(isset($baseTemplates))
		{
			if(isset($extraTemplates))
			{
				$extraTemplates = array_merge($extraTemplates, $baseTemplates);
			} else	{
				$extraTemplates = $baseTemplates;
			}
		}

		$this->__runtimeLoopProtection = false;

		return $extraTemplates;
	}

	public function getExtraNames($names)
	{
		if(!is_array($names))
			$names = array($names);

		$extraNames = array();

		foreach($names as $name) {
			$tmp = substr($name, 7);
			$rawNames[] = substr($tmp, strpos($tmp, '/'));
			$baseNames[] = 'models/base' . substr($tmp, strpos($tmp, '/'));
		}

		$descent = $this->model->getDescent();

		if(isset($descent)) {
			foreach($descent as $ancestor) {
				foreach($rawNames as $name) {
					$extraNames[] = 'models/' . $ancestor . $name;
				}
			}
		}

		$extraNames = array_merge($extraNames, $baseNames);

		return $extraNames;
	}

	public function useModel($model)
	{
		$this->model = $model;
	}
}

?>