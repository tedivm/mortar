<?php

class BentoBaseActionConfiguration extends FormPackageAction
{
	static $requiredPermission = 'System';

	public $AdminSettings = array('linkLabel' => 'System Configuration',
									'linkTab' => 'System',
									'headerTitle' => 'System Configuration',
									'linkContainer' => 'Configuration');


	protected $formName = 'BentoBaseSystemSettingsForm';

	protected function processInput($inputHandler)
	{

		$info = InfoRegistry::getInstance();
		$configPath = $info->Configuration['path']['config'] . 'configuration.php';
		$configIni = new IniFile($configPath);



		foreach(array('base', 'theme', 'config', 'mainclasses', 'modules', 'abstracts', 'engines', 'library', 'javascript') as $value)
		{
			$configIni->set('path', $value, $inputHandler['path_' . $value]);
		}

		foreach(array('theme', 'modules', 'javascript') as $value)
		{
			$configIni->set('url', $value, $inputHandler['url_' . $value]);
		}

		$modRewrite = isset($inputHandler['url_modRewrite']);
		$configIni->set('url', 'modRewrite', $modRewrite);
		return $configIni->write();

	}

	public function viewAdmin()
	{
		if($this->form->wasSubmitted())
		{
			if($this->formStatus)
			{
				$this->AdminSettings['headerSubTitle'] = 'Configuration successfully updated.';
			}else{
				$this->AdminSettings['headerSubTitle'] = 'An error has occured while trying to process this form';
			}
		}else{

		}

		$output .= $this->form->makeDisplay();
		return $output;
	}}
?>