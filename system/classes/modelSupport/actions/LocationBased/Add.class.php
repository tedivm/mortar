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

	/**
	 * This method checks to see if input was sent, validates that input through a subordinate class,
	 * passes it to the processInput class to save, and then sets the formStatus to the appropriate value.
	 *
	 */
	public function logic()
	{
		parent::logic();
		if($this->formStatus)
		{
			$modelLocation = $this->model->getLocation();
			$parentLocation = $modelLocation->getParent();
			$parentLocation->save();
		}
	}

	/**
	 * This function returns the Form class that defines our input requirements. It also merges in any format specific
	 * sub forms.
	 *
	 * @access protected
	 * @return Form
	 */
	protected function getForm()
	{
		$form = parent::getForm();
		$form = ($form instanceof Form) ? $form : new Form($this->model->getType() . 'Form' . $this->actionName);

		// If the parent location isn't set, there should be some form to do so.
		if($this->model->getLocation()->getParent() === false)
			throw new CoreError('Unspecified  parent', 400);

		if(!staticHack($this->model, 'autoName') && !$form->getInput('location_name'))
		{
			$form->createInput('location_name')->
				setLabel('Name')->
				addRule('alphanumeric')->
				addRule('required');
		}


		/*

			location_owner
			location_ownerGroup
			location_name




		*/





		$locationFormName = importClass('LocationForm', 'modelSupport/Forms/LocationForm.class.php', 'mainclasses');
		if(!isset($locationFormName))
			throw new CoreError('Unable to load LocationForm');

		$locationForm = new $locationFormName($this->type . 'Form' . $this->actionName);
		$form->merge($locationForm);


		return $form;
	}

	/**
	 * This function seperates out the specific input groups (specified by groupname_) and adds them to the object
	 * that uses them.
	 *
	 * @access protected
	 * @param array $input
	 * @return bool This is the status on whether the model was successfully saved
	 */
	protected function processInput($input)
	{
		$inputNames = array_keys($input);
		$inputGroups = $this->getInputGroups($inputNames);

		if(isset($inputGroups['model']))
			foreach($inputGroups['model'] as $name)
		{
			$this->model[$name] = $input['model_' . $name];
		}

		$location = $this->model->getLocation();

		if($location->getName() == 'tmp') // this is a new, unsaved location
		{
			$user = ActiveUser::getUser();
			$location->setOwner($user);
		}

		if(isset($input['location_name']))
		{
			$this->model->name = $input['location_name'];
		}


		return $this->model->save();
	}

	/**
	 * This takes in an array of input names and seperates them into groups, using the underscore as the group_name
	 * delimiter. model_name ends up being a value in $array['model'].
	 *
	 * @access protected
	 * @param array $inputNames
	 * @return array $array[groupName] = array(item, item, item).
	 */
	protected function getInputGroups($inputNames)
	{
		foreach($inputNames as $name)
		{
			if(strpos($name, '_') !== false)
			{
				$nameValues = explode('_', $name);
				if(isset($nameValues[1]))
				{
					$inputGroups[$nameValues[0]][] = $nameValues[1];
				}
			}
		}
		return $inputGroups;
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

	/**
	 * This function handles the view for the admin format. If the form was not submitted, or if there is an error, it
	 * gets displayed. Otherwise we redirect the output to the newly saved resource (as a way to prevent the backspace
	 * duplicate issue).
	 *
	 * @return string
	 */
	public function viewAdmin()
	{
		if($this->form->wasSubmitted())
		{
			if($this->formStatus === true)
			{

				/*
				 where should i go after post? I need to redirect somewhere to prevent duplicate form submissions
				 when people use the back button.

				 Current options-

				 	1. back to this page, with a success message
				 	2. to the edit page, also with a success message
				 	*3. to the 'read' page, which isn't really defined yet for the admin side of things
				*/

				$locationId = $this->model->getLocation()->getId();
				$url = new Url();
				$url->locationId = $locationId;
				$url->format = 'Admin';
				$url->action = 'Read';

				//add some sort of message variable so the read page can add a 'you saved' or 'you edited' thing

				$this->ioHandler->addHeader('Location', (string) $url);


			}else{
				return $this->makeDisplay();
			}
		}else{
			return $this->form->makeHtml();
		}
	}

}

?>