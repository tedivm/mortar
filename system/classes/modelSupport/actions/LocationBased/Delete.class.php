<?php
/**
 * BentoBase
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

	/**
	 * This defines the permission action that the user needs to run this. Permissions are based off of an action and
	 * a resource type, so this value is used with the model type to generate a permissions object
	 *
	 * @access public
	 * @var string
	 */
	public static $requiredPermission = 'Delete';

	protected $originalParent;

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

		$this->originalParent = $this->model->getLocation()->getParent();

		$root = new Location(1);
		$trashLocation = $root->getChildByName('Trash');

		$trashBag = ModelRegistry::loadModel('TrashBag');
		$trashBag->setParent($trashLocation);
		$trashBag->save();

		$trashBagLocation = $trashBag->getLocation();
		$this->model->setParent($trashBagLocation);
		return $this->model->save();

	}


	public function viewAdmin()
	{
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
			return $this->form->makeHtml();
		}
	}

	public function viewHtml()
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