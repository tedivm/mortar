<?php

class MortarActionDashboard extends ActionBase
{
	static $requiredPermission = 'Admin';

	public $adminSettings = array( 'headerTitle' => 'Mortar Dashboard' );

	protected $actions;

	public function viewAdmin($page) {

		$control = ControlRegistry::getControl('admin', 'Hello, World!');

		$content = new HtmlObject('ul');
		$content->addClass('dashboard');

		for ($i = 0; $i < 4; $i++) {
			$stuff = $control->getContents();
			$box = new HtmlObject('li');
			$box->addClass('dashboard_widget');
			$box->wrapAround("Box number $i");
			if($i == 0) {
				$box->wrapAround($stuff);
			}
			$content->wrapAround($box);
		}

		$clean = new HtmlObject('div');
		$clean->addClass('clean');
		$content->wrapAround($clean);

		return (string) $content;

	}
}

?>