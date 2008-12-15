<?php

class BentoBaseSystemSettingsForm extends Form
{
	protected function define()
	{
		$info = InfoRegistry::getInstance();
		$configIni = new IniFile($info->Configuration['path']['config'] . 'configuration.php');



		$cacheHandlers = Cache::getHandlers();

		$handlerInput = $this->createInput('cacheHandler');

		$handlerInput->setType('select')->
				setLabel('Cache Method');

		foreach($cacheHandlers as $handlerName => $handlerClass)
		{
			$attributes = array();
			if($configIni->get('cache', 'handler') == $handlerName)
				$attributes = array('selected' => 'selected');
			$handlerInput->setOptions($handlerName, $handlerName, $attributes);
		}


		$this->changeSection('url')->
			createInput('url_modRewrite')->
				setType('checkbox')->
				setLabel('Enable Url Rewriting')->
				check($configIni->get('url', 'modRewrite'));

		$this->createInput('url_theme')->
					setLabel('Theme Alias')->
					property('value', $configIni->get('url', 'theme'));

		$this->createInput('url_modules')->
					setLabel('Modules Alias')->
					property('value', $configIni->get('url', 'modules'));

		$this->createInput('url_javascript')->
					setLabel('Javascript Alias')->
					property('value', $configIni->get('url', 'javascript'));

		$this->changeSection('Paths')->
				createInput('path_base')->
					setLabel('Installation Base')->
					property('value', $configIni->get('path', 'base'));

		$this->createInput('path_theme')->
					setLabel('Theme')->
					property('value', $configIni->get('path', 'theme'));

		$this->createInput('path_config')->
					setLabel('Configuration')->
					property('value', $configIni->get('path', 'config'));

		$this->createInput('path_mainclasses')->
					setLabel('Main Classes')->
					property('value', $configIni->get('path', 'mainclasses'));

		$this->createInput('path_modules')->
					setLabel('Packages')->
					property('value', $configIni->get('path', 'modules'));

		$this->createInput('path_abstracts')->
					setLabel('Abstract Classes')->
					property('value', $configIni->get('path', 'abstracts'));

		$this->createInput('path_engines')->
					setLabel('Engine Classes')->
					property('value', $configIni->get('path', 'engines'));

		$this->createInput('path_javascript')->
					setLabel('Javascript')->
					property('value', $configIni->get('path', 'javascript'));

		$this->createInput('path_temp')->
					setLabel('Temporary Files')->
					property('value', $configIni->get('path', 'temp'));

		$this->createInput('path_library')->
					setLabel('Shared Library')->
					property('value', $configIni->get('path', 'library'));


		$this->createInput('path_interfaces')->
					setLabel('Interfaces')->
					property('value', $configIni->get('path', 'interfaces'));


	}
}

?>