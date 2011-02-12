<?php

class MortarCoreMaintenanceModeForm extends Form
{
	protected function define()
	{
		$config = Config::getInstance();

		$this->changeSection('base');
		$this->setLegend('Maintenance Options');

		$isEnabled = isset($config['system']['maintenance']) && $config['system']['maintenance'];
		$this->createInput('enable')->
			setType('checkbox')->
			setLabel('Disable System')->
			check($isEnabled);

		$message = $this->createInput('message')->
			setType('textarea')->
			setLabel('Message');

		if(isset($config['system']['maintenanceMessage']))
			$message->setValue($config['system']['maintenanceMessage']);
	}

}

?>