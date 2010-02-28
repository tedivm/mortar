<?php

class MortarActionDashboardSettings extends FormAction
{

	static $requiredPermission = 'System';

	public $adminSettings = array( 'headerTitle' => 'Dashboard Settings' );

	protected $dashboardSlots = 12;

	public function getForm()
	{
		$form = new Form('DashboardSettings');

		$form->changeSection('controls')->
			setLegend('Controls');

		$user = ActiveUser::getUser();
		$controls = new ControlSet($user->getId());
		$controls->loadControls();
		$cInfo = $controls->getInfo();

		$allControls = ControlRegistry::getControls('admin');

		for($i = 0; $i < $this->dashboardSlots; $i++) {
			$input = $form->createInput('dashboard_slot_' . $i)->
				setLabel('Control #' . $i)->
				setType('select')->
				setOptions('', null, null);

			foreach($allControls as $control) {
				$select = (isset($cInfo[$i]) && $cInfo[$i]['name'] === $control['name'])
					? array('selected' => 'yes')
					: null;
				$input->setOptions($control['name'], $control['name'], $select);
			}
		}

		return $form;
	}

	public function processInput($inputHandler) {
	
	}

	public function viewAdmin($page)
	{
		if($this->formStatus) {
			$output = "Successfully submitted.";
		} else {
			$output = $this->form->getFormAs();
		}
		return $output;
	}
}

?>