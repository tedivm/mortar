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

		if(isset($descent))
		{
			foreach($descent as $ancestor)
			{
				$ancestorTemplates = $this->loadTemplateSet('models/' . $ancestor . '/' . $baseName);
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

	public function useModel($model)
	{
		$this->model = $model;
	}
}

?>