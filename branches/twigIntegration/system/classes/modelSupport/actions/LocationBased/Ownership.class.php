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
class ModelActionLocationBasedOwnership extends ModelActionLocationBasedEdit
{
	/**
	 * This defines the permission action that the user needs to run this. Permissions are based off of an action and
	 * a resource type, so this value is used with the model type to generate a permissions object
	 *
	 * @access public
	 * @var string
	 */
	public static $requiredPermission = 'Admin';


	protected function getForm()
	{
		$form = new Form('location_ownership');
		$form->changeSection('owners');
		$form->setLegend('Owners');


		$ownerInput = $form->createInput('owner')->
			setType('user')->
			setLabel('Owner');

		$location = $this->model->getLocation();

		if($owner = $location->getOwner())
		{
			$ownerInput->setValue($owner->getId());
		}

		$ownerGroupInput = $form->createInput('ownerGroup')->
			setType('membergroup')->
			setLabel('Membergroup');

		if($ownerGroup = $location->getOwnerGroup())
		{
			$ownerGroupInput->setValue($ownerGroup->getId());
		}

		return $form;
	}

	protected function processInput($input)
	{
		$location = $this->model->getLocation();
		if(isset($input['owner']) && is_numeric($input['owner']))
		{
			if(!($user = ModelRegistry::loadModel('User', $input['owner'])))
				return false;
			$location->setOwner($user);
		}

		if(isset($input['ownerGroup']) && is_numeric($input['ownerGroup']))
		{
			$membergroup = new MemberGroup($input['ownerGroup']);
			if(!$membergroup->getId())
				return false;

			$location->setOwnerGroup($membergroup);
		}

		return $location->save();
	}

	protected function onSuccess()
	{

	}


}

?>