<?php

class TwigIntegrationThemeEnvironment extends Twig_Environment
{
	protected $generationDelimiter = ':';

	public function loadTemplate($name)
	{
		if($name == 'parent')
			throw new CoreError('Templates can not be named parent.');

		if($generationDelimiterPosition = strpos($name, $this->generationDelimiter))
		{
			$generation = substr($name, 0, $generationDelimiterPosition);
			$name = substr($name, $generationDelimiterPosition);
		}

		$name = ltrim($name, $this->generationDelimiter);

		if(!($templateSet = $this->loader->loadTemplateSet($name)))
			throw new CoreError('Unable to load template ' . $name);

		// if no generation is set assume its the highest level available
		if(!isset($generation))
		{
			$templates = array_keys($templateSet);
			$className = array_shift($templates);
		}elseif(isset($templateSet[$generation])){
			$className = $generation . $this->generationDelimiter . $name;
		}else{
			throw new CoreError('Unable to load template ' . $name . ' from collection ' . $generation);
		}
		return parent::loadTemplate($className);
	}
}

?>