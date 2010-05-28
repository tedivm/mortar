<?php

class TwigIntegrationThemeEnvironment extends Twig_Environment
{
	protected $generationDelimiter = ':';

	public function loadTemplate($names)
	{ 
		if(!is_array($names))
			$names = array($names);

		if(in_array('parent', $names))
			throw new TwigThemeError('Templates can not be named parent.');

		$templateList = array();
		$useGen = array();

		foreach($names as $fullname) {
			$namePieces = $this->loader->getNamePieces($fullname);
			
			if(isset($namePieces['generation'])) {
				$generation = $namePieces['generation'];
				$name = $namePieces['name'];
				$useGen[$fullname] = array('gen' => $generation, 'name' => $name);
			} else {
				$name = $fullname;
			}

			if(!($templateSet = $this->loader->loadTemplateSet($name)))
				continue;

			$templateList = array_merge($templateList, $templateSet);
		}

		$gens = array_keys($this->loader->getPaths());

		$names = array_merge($names, $this->loader->getExtraNames($names));

		foreach($gens as $generation) {
			foreach($names as $name) {
				if(isset($useGen[$name])) {
					$pieces = $useGen[$name];
					if($pieces['gen'] == $generation && isset($templateList[$name])) {
						$className = $name;
						break 2;
					}
				} else {
					if(isset($templateList[$generation . $this->generationDelimiter . $name])){
						$className = $generation . $this->generationDelimiter . $name;
						break 2;				
					}
				}
			}
		}

		if(!isset($className)) {
			$nameList = '( ';
			foreach($names as $name) $nameList .= $name . ' ';
			$nameList .= ' )';
			throw new TwigThemeError('Unable to load template from list ' . $nameList);
		}

		return parent::loadTemplate($className);
	}
}

class TwigThemeError extends CoreError {}
?>