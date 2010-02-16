<?php

class MortarActionDashboard extends ActionBase
{
	static $requiredPermission = 'Admin';

	public $adminSettings = array( 'headerTitle' => 'Mortar Dashboard' );

	protected $actions;

	public function viewAdmin($page) {

		$location = new Location(37);
		$model = $location->getResource();

		$this->actions = array(	array('action' => 'MortarActionInstallModule', 'argument' => ''),
						array('action' => 'MortarActionSiteRead', 'argument' => ActiveSite::getSite()),
						array('action' => 'MortarAction', 'argument' => $model),
						array('action' => '', 'argument' => '')	);

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