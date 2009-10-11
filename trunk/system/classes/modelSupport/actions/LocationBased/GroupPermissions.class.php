<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ModelSupport
 */

/**
 * This class handles editing resources that are already present in the system. It is largely based on the
 * ModelActionEdit class.
 *
 * @package System
 * @subpackage ModelSupport
 */

class ModelActionLocationBasedGroupPermissions extends ModelActionLocationBasedAdd {

	public static $requiredPermission = 'Admin';

	protected function getForm()
	{
		$form = new Form('group_permissions');

		$form->changeSection('group')->setLegend('Group');

		$form->createInput('group')->setType('membergroup')->setLabel('Group');

		return $form;
	}

	protected function processInput($input)
	{
                return $this->model->save();
	}

	protected function getRedirectUrl()
	{
		$query = Query::getQuery();
		$inputs = $this->form->checkSubmit();
                $group = isset($inputs['group']) ? $inputs['group'] : null;

                $locationId = $this->model->getLocation()->getId();
                $url = new Url();
                $url->locationId = $locationId;
                $url->format = $query['format'];
                $url->action = isset($group) ? 'EditGroupPermissions' : 'GroupPermissions';
                if(isset($group))
                	$url->property('group', $group);
                return $url;
	}

}


?>