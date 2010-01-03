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

        public $adminSettings = array( 'headerTitle' => 'Add' );

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

		$form->changeSection('location_information')->setLegend('Location Information');

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

		$query = Query::getQuery();
		if($query['format'] == 'Admin' && $statusTypes = $this->model->getStatusTypes())
		{
			$selectInput = $form->createInput('location_status')->
				setLabel('Status')->
				setType('select');

			foreach($statusTypes as $type)
				$selectInput->setOptions($type, $type);

			if(isset($this->model->status))
				$selectInput->setValue($this->model->status);
		}

		$form->createInput('location_publishDate')->
			setType('datetime')->
			setLabel('Publish Date')->
			setValue(date( 'm/d/y h:i a'));

		$form->createInput('location_theme')->
			setLabel('Theme')->
			addRule('alphanumeric');
		
		$form->createInput('location_template')->
			setLabel('Template')->
			addRule('alphanumeric');

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
		
		if(isset($input['location_publishDate']))
		{
			$location->setPublishDate($input['location_publishDate']);
		}
		if(isset($input['location_theme']))
		{
			if($input['location_theme'] === '')
				$location->unsetMeta('htmlTheme');
			else
				$location->setMeta('htmlTheme', $input['location_theme']);
		}
		if(isset($input['location_template']))
		{
			if($input['location_template'] === '')
				$location->unsetMeta('pageTemplate');
			else
				$location->setMeta('pageTemplate', $input['location_template']);
		}

		$query = Query::getQuery();
		if($query['format'] == 'Admin')
		{
			$statusTypes = $this->model->getStatusTypes();
			if(isset($input['location_status']) && in_array($input['location_status'], $statusTypes))
			{
				$this->model->status = $input['location_status'];
			}
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