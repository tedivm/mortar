<?php

class MortarActionDashboardSettings extends FormAction
{
	static $requiredPermission = 'System';

	public static $settings = array( 'Base' => array( 'headerTitle' => 'Dashboard Settings' ) );

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
				setLabel('Control #' . ($i+1))->
				setType('select')->
				setOptions('', null, null);

			foreach($allControls as $control) {
				$select = (isset($cInfo[$i]) && $cInfo[$i]['name'] === $control['name'])
					? array('selected' => 'yes')
					: null;
				$input->setOptions($control['id'], $control['name'], $select);
			}
		}

		return $form;
	}

	public function processInput($inputHandler) {
		$user = ActiveUser::getUser();
		$cs = new ControlSet($user->getId());

		for($i = 0; isset($inputHandler['dashboard_slot_' . $i]); $i++) {
			$cs->addControl($inputHandler['dashboard_slot_' . $i], null, null);
		}

		$cs->saveControls();

		return true;		
	}

	public function viewAdmin($page)
	{
                $output = '';
                if($this->form->wasSubmitted()) {
                	$output .= '<h3>Settings Saved</h3>';
                }

                $output .= $this->form->getFormAs('Html');
                return $output;
	}

	public function viewHtml($page)
	{
		return $this->viewAdmin($page);
	}

}

?>