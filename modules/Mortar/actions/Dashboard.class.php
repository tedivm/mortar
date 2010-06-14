<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Dashboard
 */

/**
 * The Dashboard action actually displays a set of registered Controls for a user. Basically all the heavy lifting
 * is done elsewhere; this action just wraps some HTML around it.
 *
 * @package Mortar
 * @subpackage Dashboard
 */
class MortarActionDashboard extends ActionBase
{
	static $requiredPermission = 'Admin';

	public static $settings = array( 'Base' => array( 'headerTitle' => 'Dashboard' ) );

	/**
	 * Wraps HTML around a series of Controls which are loaded via the ControlSet class and displayed using the
	 * ViewControlDisplay. It also generates a small "Add Control" form using data from the ControlRegistry
	 * whose results are passed along to the ControlModify action.
	 *
	 * @param Page $page
	 * @return string
	 */
	public function viewAdmin($page) {
		$query = Query::getQuery();
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
		$link->format = $query['format'];
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

		return (string) $content;
	}

	public function viewHtml($page)
	{
		return $this->viewAdmin($page);
	}
}

?>