<?php

class InstallerInstallationForm extends Form
{
	protected function define()
	{
		$config = Config::getInstance();

		$this->disableXsfrProtection();

		$this->changeSection('system')->
				setLegend('System Information')->

				createInput('setup_location_root_Site_name')->
					setLabel('Site Name')->
					addRule('required')->
					addRule('letterswithbasicpunc')->
				getForm()->

				createInput('setup_location_root_Site_model_functions_addUrl_0')->
					setLabel('Domain')->
					property('value', $_SERVER['SERVER_NAME'] . substr($_SERVER['PHP_SELF'], 0,
														strpos($_SERVER['PHP_SELF'], DISPATCHER)))->
					addRule('required')->
				getForm()->

				createInput('url_modRewrite')->
					setType('checkbox')->
					setLabel('Enable Url Rewriting')->
				getForm();


				$timezoneHandler = $this->createInput('system_timezone');
				$timezoneHandler->setType('select')->
						setLabel('Time Zone');

				$timezones = DateTimeZone::listIdentifiers();
				$currentTimezone = 'US/Eastern';

				if(in_array('US/Eastern', $timezones))
				{
					$currentTimezone = 'US/Eastern';
				}elseif(in_array('America/New_York', $timezones)){
					$currentTimezone = 'America/New_York';
				}else{
					$currentTimezone = 'UTC';
				}

				foreach($timezones as $timezone)
				{

					$attributes = array();
					if($currentTimezone == $timezone)
						$attributes = array('selected' => 'selected');

					$timezoneLabel = str_replace('_', ' ', $timezone);
					$timezoneHandler->setOptions($timezone, $timezoneLabel, $attributes);
				}

				$this->createInput('base')->
					setLabel('Base Path')->
					property('value', $config['path']['base'])->
					addRule('required')->
				getForm()->

			changeSection('admin')->
				setLegend('Administrative User')->

				createInput('setup_user_admin_name')->
					setLabel('Username')->
					property('value', 'admin')->
					addRule('required')->
				getForm()->

				createInput('setup_user_admin_password')->
					setType('password')->
					setLabel('Password')->
					addRule('required')->
					addRule('minlength', '8');

			$cacheInput = $this->changeSection('cache')->
				setLegend('Cache Settings')->

				createInput('cacheHandler')->
					setLabel('Cache Handler')->
					setType('select')->
					setValue('SQLite')->
					addRule('required');

				$cacheHandlers = Cache::getHandlers();
				foreach($cacheHandlers as $cacheName => $cacheClass)
				{
					$cacheInput->setOptions($cacheName, $cacheName);
				}

			$this->changeSection('mainDatabase')->
				setLegend('Main Database Connection')->
				setSectionIntro('This is the primary database connection. This user needs to have full access
									to the database.')->

				createInput('DBname')->
					setLabel('Database')->
					addRule('required')->
				getForm()->

				createInput('DBusername')->
					setLabel('User')->
					addRule('required')->
				getForm()->

				createInput('DBpassword')->
					setLabel('Password')->
					addRule('required')->
					property('autocomplete', 'off')->
				getForm()->

				createInput('DBhost')->
					setLabel('Host')->
					property('value', 'localhost')->
					addRule('required')->
				getForm()->

				createInput('blowoutDatabase')->
					setType('checkbox')->
					check(false)->
					setLabel('Purge Database')->
					setDescription('Clear out conflicting tables in a populated database (suggested for development work only)')->
				getForm()->

			changeSection('readonlyDatabase')->
				setLegend('Read Only Database Connection')->
				setSectionIntro('This is the read only database connection, which all of the select statements use.
									 If you do not have a seperate user for this you may leave it blank.')->

				createInput('DBROname')->
					setLabel('Database')->
				getForm()->

				createInput('DBROusername')->
					setLabel('User')->
				getForm()->

				createInput('DBROpassword')->
					setLabel('Password')->
					property('autocomplete', 'off')->
				getForm()->

				createInput('DBROhost')->
					setLabel('Host')->
					property('value', 'localhost');
	}
}




?>