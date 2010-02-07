<?php

class MortarActionMaintenanceMode extends FormAction
{
	static $requiredPermission = 'System';

	public $adminSettings = array( 'headerTitle' => 'Maintenance Mode' );

	protected $formName = 'MortarMaintenanceModeForm';

	protected function processInput($inputHandler)
	{
		$config = Config::getInstance();
		$file = $config->getSettingsFile();

		if(isset($inputHandler['enable']) && $inputHandler['enable'] == true)
		{
			$file->set('system', 'maintenance', true);
		}else{
			$file->set('system', 'maintenance', false);
		}

		if(isset($inputHandler['message']))
		{
			$file->set('system', 'maintenanceMessage', $inputHandler['message']);
		}else{
			$file->set('system', 'maintenanceMessage', null);
		}

		$file->write();
		return true;
	}

	public function viewAdmin($page)
	{
		$this->setTitle($this->adminSettings['headerTitle']);

		$output = '';
		if($this->formStatus)
		{
			$output = '<h3>Settings Updated</h3>';
		}
		$output .= $this->form->getFormAs();
		return $output;
	}

}


?>