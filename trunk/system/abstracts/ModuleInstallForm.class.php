<?php

abstract class ModuleInstallForm
{
	protected $settings = array();
	protected $packageName = '';
	
	abstract public function getForm();

	public function getSettings($inputHandler)
	{
		$settings = array();
		foreach($this->settings as $settingName)
		{
			$savedName = substr($settingName, strlen($this->packageName));
			if(isset($inputHandler[$packageName . '_' .$settingName]))
				$settings[$savedName] = $inputHandler[$settingName];
		}

		return $settings;
	}
}

?>