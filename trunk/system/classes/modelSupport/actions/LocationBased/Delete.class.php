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
 * This class handles deleting resources from the system.
 *
 * @package System
 * @subpackage ModelSupport
 * @todo write this
 */
class ModelActionLocationBasedDelete extends ModelActionLocationBasedEdit
{

        public $adminSettings = array( 'headerTitle' => 'Delete' );
        public $htmlSettings = array( 'headerTitle' => 'Delete' );

	/**
	 * This defines the permission action that the user needs to run this. Permissions are based off of an action and
	 * a resource type, so this value is used with the model type to generate a permissions object
	 *
	 * @access public
	 * @var string
	 */
	public static $requiredPermission = 'Delete';

	protected $originalParent;

	/**
	 * This method checks to see if input was sent, validates that input through a subordinate class,
	 * passes it to the processInput class to save, and then sets the formStatus to the appropriate value.
	 *
	 */
	public function logic()
	{
		$modelLocation = $this->model->getLocation();
		$parentLocation = $modelLocation->getParent();
		parent::logic();
		if($this->formStatus)
			$parentLocation->save();
	}

	protected function getForm()
	{
		$form = new Form($this->model->getType() . 'Delete');
		$form->createInput('confirm')->setLabel('Confirm')->setType('checkbox')->addRule('required');
		return $form;
	}

	protected function processInput($input)
	{
		if(!isset($input['confirm']))
			return false;

		$this->originalParent = $this->model->getLocation()->getParent()->getId();

		$root = new Location(1);
		$trashLocation = $root->getChildByName('Trash');

		$trashBag = ModelRegistry::loadModel('TrashBag');
		$trashBag['originLocation'] = $this->originalParent;
		$trashBag->setParent($trashLocation);
		$trashBag->save();

		$trashBagLocation = $trashBag->getLocation();
		$this->model->setParent($trashBagLocation);
		return $this->model->save();

	}


	public function viewAdmin($page)
	{
	        $this->setTitle($this->adminSettings['headerTitle']);

		if($this->formStatus)
		{
			if(isset($this->originalParent) && is_numeric($this->originalParent))
			{
				$url = new Url();
				$url->location = $this->originalParent;
				$url->format = 'admin';
				$this->ioHandler->addHeader('Location', (string) $url);
			}else{

			}
			return 'Item successfully deleted';
		}else{
			return $this->form->getFormAs('Html');
		}
	}

	public function viewHtml($page)
	{
		return '';
	}

	public function viewXml()
	{
		return (bool) $this->formStatus;
	}

	public function viewJson()
	{
		return (bool) $this->formStatus;
	}
}

?>