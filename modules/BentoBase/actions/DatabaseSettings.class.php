<?php

class BentoBaseActionDatabaseSettings extends ActionBase
{
	static $requiredPermission = 'System';

	public $AdminSettings = array('linkLabel' => 'Database Connections',
									'linkTab' => 'System',
									'headerTitle' => 'Database Connections',
									'linkContainer' => 'Configuration');


	protected $form;
	protected $success = false;

	public function logic()
	{
		$form = new Form('DatabaseSettings');
		$this->form = $form;
		$info = InfoRegistry::getInstance();
		$dbPath = $info->Configuration['path']['config'] . 'databases.php';

		$iniFile = new IniFile($dbPath);


		$form->changeSection('mainDatabase')->
			setLegend('Main Database Connection')->
			setSectionIntro('This is the primary database connection. This user needs to have full access to the database.')->

			createInput('DBname')->
				setLabel('Database')->
				addRule('required')->
				property('value', $iniFile->get('default', 'dbname'))->
			getForm()->

			createInput('DBusername')->
				setLabel('User')->
				addRule('required')->
				property('value', $iniFile->get('default', 'username'))->
			getForm()->

			createInput('DBpassword')->
				setLabel('Password')->
				addRule('required')->
				setType('password')->
			getForm()->

			createInput('DBhost')->
				setLabel('Host')->
				property('value', $iniFile->get('default', 'host'))->
				addRule('required')->

			getForm()->

		changeSection('readonlyDatabase')->
			setLegend('Read Only Database Connection')->
			setSectionIntro('This is the read only database connection, which all of the select statements use. If you do not have a seperate user for this you may leave it blank.')->

			createInput('DBROname')->
				setLabel('Database')->
				property('value', $iniFile->get('default_read_only', 'dbname'))->
			getForm()->

			createInput('DBROusername')->
				setLabel('User')->
				property('value', $iniFile->get('default_read_only', 'username'))->
			getForm()->

			createInput('DBROpassword')->
				setType('password')->
				setLabel('Password')->
			getForm()->

			createInput('DBROhost')->
				setLabel('Host')->
				property('value', $iniFile->get('default_read_only', 'host'));

		if($form->checkSubmit())
		{
			$inputHandler = $form->getInputhandler();


			$iniFile->set('default', 'dbname', $inputHandler['DBname']);
			$iniFile->set('default', 'username', $inputHandler['DBusername']);

			if(strlen($inputHandler['DBpassword']) > 0)
				$iniFile->set('default', 'password', $inputHandler['DBpassword']);

			$iniFile->set('default', 'host', $inputHandler['DBhost']);


			$iniFile->set('default_read_only', 'dbname', $inputHandler['DBROname']);
			$iniFile->set('default_read_only', 'username', $inputHandler['DBROusername']);

			if(strlen($inputHandler['DBROpassword']) > 0)
				$iniFile->set('default_read_only', 'password', $inputHandler['DBROpassword']);

			$iniFile->set('default_read_only', 'host', $inputHandler['DBROhost']);

			if($iniFile->write())
				$this->success = false;
		}
	}

	public function viewAdmin()
	{
		if($this->success)
			$this->AdminSettings['headerSubTitle'] = 'Database Settings Updated';

		$output = $this->form->makeDisplay();
		return $output;
	}

}


?>