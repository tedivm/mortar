<?php

class ModelActionLocationBasedThemeInfo extends ModelActionLocationBasedAdd
{
        public static $settings = array( 'Base' => array('headerTitle' => 'Theme Settings', 'useRider' => false) );

	public static $requiredPermission = 'Theme';

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
			$theme = new Theme($themeName[0]);
			$settings = $theme->getSettings();
			if(strtolower($settings['meta']['format']) === 'html') {
				$themes[] = $themeName[0];
			}
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
			setType('template')->
			property('using', 'theme')->
			setLabel('Template')->
			addRule('alphanumeric')->
			setValue($locTemp)->
			property('theme', $locTheme);

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
		$user = ActiveUser::getUser();

		if( isset($input['preview']) && ($input['preview'] === 'Preview') )
			return true;

		$location = $this->model->getLocation();

		if(isset($input['theme']))
		{
			if($input['theme'] === '') {
				$location->unsetMeta('htmlTheme');
				ChangeLog::logChange($this->model, 'theme unset', $user, 'Edit');
			} else {
				$location->setMeta('htmlTheme', $input['theme']);
				ChangeLog::logChange($this->model, 'theme set', $user, 'Edit', $input['theme']);
			}
		}
		if(isset($input['template']))
		{
			if($input['template'] === '') {
				$location->unsetMeta('pageTemplate');
				ChangeLog::logChange($this->model, 'template unset', $user, 'Edit');
			} else {
				$location->setMeta('pageTemplate', $input['template']);
				ChangeLog::logChange($this->model, 'template set', $user, 'Edit', $input['template']);
			}
		}

		return $this->model->save();
	}

	public function viewAdmin($page)
	{
		$output = parent::viewAdmin($page);
		return $output;
	}

}


?>