<?php

class TwigIntegrationModelLoader extends TwigIntegrationThemeLoader
{
	protected $__runtimeLoopProtection = false;

	protected function loadExtraClasses($name)
	{
		if($this->__runtimeLoopProtection)
			return array();
		$this->__runtimeLoopProtection = true;


		$tmp = substr($name, 7);
		$baseName = substr($tmp, strpos($tmp, '/'));

		$baseTemplates = $this->loadTemplateSet('models/base' . $baseName);
		$this->__runtimeLoopProtection = false;

		return $baseTemplates;
	}
}

?>