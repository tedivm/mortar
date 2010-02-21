<?php

class MortarActionDashboard extends ActionBase
{
	static $requiredPermission = 'Admin';

	public $adminSettings = array( 'headerTitle' => 'Mortar Dashboard' );

	protected $actions;

	public function viewAdmin($page) {
		$content = new HtmlObject('ul');
		$content->addClass('dashboard');

		for ($i = 0; $i < 4; $i++) {
			$box = new HtmlObject('li');
			$box->addClass('dashboard_widget');
			$box->wrapAround("Box number $i");
			$content->wrapAround($box);
		}

		$clean = new HtmlObject('div');
		$clean->addClass('clean');
		$content->wrapAround($clean);

		return (string) $content;
	}
}

?>