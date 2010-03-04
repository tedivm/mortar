<?php

class MortarActionDashboard extends ActionBase
{
	static $requiredPermission = 'Admin';

	public $adminSettings = array( 'headerTitle' => 'Mortar Dashboard' );

	protected $actions;

	public function viewAdmin($page) {
		$user = ActiveUser::getUser();
		$theme = $page->getTheme();

		$cs = new ControlSet($user->getId());
		$cs->loadControls();
		$cd = new ViewControlDisplay($cs, $theme);
		$dash = $cd->getDisplay();

		$content = new HtmlObject('ul');
		$content->addClass('dashboard');

		$content->wrapAround($dash);

		$clean = new HtmlObject('div');
		$clean->addClass('clean');
		$content->wrapAround($clean);

		$link = new Url();
		$link->module = 'Mortar';
		$link->format = 'admin';
		$link->action = 'ControlModify';

		$form = new Form('dashboard_add_control');
		
		$form->setLegend('Add Control')->
			setAction($link)->
			setMethod('post');

		$input = $form->createInput('id')->
			setLabel('Add Control')->
			setType('select');

		$controls = ControlRegistry::getControls('admin');
		foreach($controls as $control) {
			$input->setOptions($control['id'], $control['name'], null);
		}

		$form->createInput('modify')->
			setType('submit')->
			setValue('Add');

		$form->createInput('user')->
			setType('hidden')->
			setValue($user->getId());

		$content .= $form->getFormAs();

		$page->setTitle('Dashboard');
		return (string) $content;
	}
}

?>