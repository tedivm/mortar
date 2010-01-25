<?php

class ModelActionLocationBasedThemeInfo extends ModelActionLocationBasedAdd
{

	public $adminSettings = array( 'headerTitle' => 'Theme Settings' );

	public static $requiredPermission = 'Admin';

	protected function getForm()
	{
		$locTheme = $this->model->getLocation()->getMeta('htmlTheme', true);
		$locTemp = $this->model->getLocation()->getMeta('pageTemplate', true);

		$config = Config::getInstance();
		$themePath = $config['path']['theme'];

		$themes = array('');
		$installedThemes = glob($themePath . "*", GLOB_ONLYDIR);
		foreach($installedThemes as $themePath) {
			$themeName = array_reverse(explode('/', $themePath));
			$themes[] = $themeName[0];
		}

		$form = new Form('theme_info');

                $form->changeSection('theme')->setLegend('Theme Settings');

		$input = $form->createInput('theme')->
			setLabel('Theme')->
			setType('select');

		foreach($themes as $theme) {
			if($locTheme === $theme)
				$selected = array('selected' => 'yes');
			else
				$selected = array();

			$input->setOptions($theme, $theme, $selected);
		}

		$form->createInput('template')->
			setLabel('Template')->
			addRule('alphanumeric')->
			setValue($locTemp);

		return $form;
	}

	protected function processInput($input)
	{
		$location = $this->model->getLocation();
	
		if(isset($input['theme']))
		{
			if($input['theme'] === '')
				$location->unsetMeta('htmlTheme');
			else
				$location->setMeta('htmlTheme', $input['theme']);
		}
		if(isset($input['template']))
		{
			if($input['template'] === '')
				$location->unsetMeta('pageTemplate');
			else
				$location->setMeta('pageTemplate', $input['template']);
		}

		return $this->model->save();
	}

	public function viewAdmin()
	{
		$output = parent::viewAdmin();
		$this->setTitle($this->adminSettings['headerTitle']);
		return $output;
	}

}


?>