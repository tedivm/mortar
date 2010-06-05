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
 * This class is called if a model needs to be created but does not have an action class to do so.
 *
 * @package System
 * @subpackage ModelSupport
 */
class ModelActionLocationBasedAdd extends ModelActionAdd
{

	/**
	 * This defines the permission action that the user needs to run this. Permissions are based off of an action and
	 * a resource type, so this value is used with the model type to generate a permissions object
	 *
	 * @access public
	 * @var string
	 */
	public static $requiredPermission = 'Add';

	/**
	 * This is the model that the new model is going to be attached to
	 *
	 * @access protected
	 * @var Model
	 */
	protected $parentModel;

	/**
	 * This tracks the status of the user request
	 *
	 * @access protected
	 * @var bool
	 */
	protected $formStatus = false;

	protected function onSuccess()
	{
		$modelLocation = $this->model->getLocation();
		$parentLocation = $modelLocation->getParent();
		$parentLocation->save();
	}

	/**
	 * This loads the model-specific form if it exists, otherwise it makes a generic LocationModelForm.
	 *
	 * @access protected
	 * @return Form
	 */
	protected function getForm()
	{
		if(!$form = parent::getForm()) {
			$formDisplayName = $this->type . 'Form' . $this->actionName;
			$form = new LocationModelForm($formDisplayName, $this->model, $this->actionName);
		}

		return $form;
	}


	/**
	 * This class checks to make sure the user has permission to access this action. Because this model needs to be
	 * added to an existing location, that location is what gets checked by this function.
	 *
	 * @param string $action
	 * @return bool
	 */
	public function checkAuth($action = NULL)
	{
		$action = isset($action) ? $action : staticHack(get_class($this), 'requiredPermission');
		$parentLocation = $this->model->getLocation()->getParent();
		$parentModel = $parentLocation->getResource();
		return $parentModel->checkAuth($action);
	}

	/**
	 * Here we are overloading the parent class to prevent certain headers from being sent to the http views.
	 *
	 * @access protected
	 */
	protected function setHeaders()
	{

	}

	protected function getRedirectUrl()
	{
		$query = Query::getQuery();
		$locationId = $this->model->getLocation()->getId();
		$url = new Url();
		$url->locationId = $locationId;
		$url->format = $query['format'];
		$url->action = 'Read';
		return $url;
	}

}

?>