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

		$form->createInput('preview')->
			setType('submit')->
			property('value', 'Preview');

		$form->createInput('save')->
			setType('submit')->
			property('value', 'Save');

		return $form;
	}

	protected function getRedirectUrl()
	{
		$query = Query::getQuery();
		$input = $this->form->checkSubmit();

		if( isset($input['preview']) && ($input['preview'] === 'Preview') ) {
			$location = $this->model->getLocation();

			$url = new Url();
			$url->location = $location->getId();
			$url->format = 'html';
			$url->action = 'ThemePreview';

			if(isset($input['theme']) && $input['theme'] !== '')
				$url->theme = $input['theme'];

			if(isset($input['template']) && $input['template'] !== '')
				$url->template = $input['template'];

			return $url;
		} else {
			return parent::getRedirectUrl();
		}
	}

	protected function processInput($input)
	{
		if( isset($input['preview']) && ($input['preview'] === 'Preview') )
			return true;

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