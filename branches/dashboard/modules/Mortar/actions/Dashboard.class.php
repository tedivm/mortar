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

		return (string) $content;
	}
}

?>