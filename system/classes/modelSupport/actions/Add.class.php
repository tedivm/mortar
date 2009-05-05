<?php

class ModelActionAdd  extends ModelActionBase// implements ActionInterface //extends FormActionBase
{
	protected $parentModel;
	protected $model;

	protected $formStatus = false;

	public function logic()
	{
		$query = Query::getQuery();
		$this->form = $this->getForm();

//		$form = new Form();

		if($this->form->wasSubmitted())
		{
			$inputs = $this->form->checkSubmit();
			if($inputs && $this->formStatus = $this->processInput($inputs))
			{
				$this->formStatus = true;
			}else{
				$this->ioHandler->setStatusCode(400);
			}
		}
	}

	protected function getForm()
	{
		$query = Query::getQuery();
		$formName = $this->type . 'Form';
		$formClassName = importFromModule($formName, $this->model->getModule(), 'class', true);

		$form = new $formClassName($formName . $this->actionName);

		$formExtension = $this->type . $query['format'] . 'Form';
		$formExtensionClassName = importFromModule($formExtension, $this->model->getModule(), 'class');

		if($formExtensionClassName)
		{
			$formExtentionObject = new $formClassName('this will be erased when they merge :-(');
			$form->merge($formExtentionObject);
		}

		// If the parent location isn't set, there should be some form to do so.
		if($this->model->getLocation()->getParent() === false)
		{
			throw new BentoError('Unspecified  parent', 400);
		}else{
			$locationFormName = importClass('LocationForm', 'modelSupport/Forms/LocationForm.class.php',
									 'mainclasses', true);
			$locationForm = new $locationFormName('j');
			$form->merge($locationForm);
			// parent is set
		}
		return $form;
	}

	protected function processInput($input)
	{
		$inputNames = array_keys($input);
		$inputGroups = $this->getInputGroups($inputNames);

		foreach($inputGroups['model'] as $name)
		{
			$this->model[$name] = $input['model_' . $name];
		}

		$location = $this->model->getLocation();

		if($location->getName() == 'tmp') // this is a new, unsaved location
		{
			$user = ActiveUser::getCurrentUser();
			$location->setOwner($user);
		}

		if(isset($input['location_name']))
		{
			$this->model->name = $input['location_name'];
		}


		return $this->model->save();
	}

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

	protected function setPermissionObject()
	{
		$user = ActiveUser::getInstance();
		$this->permissionObject = new Permissions($this->model->getLocation()->getParent(), $user);
	}

	protected function setHeaders()
	{

	}

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
				$this->ioHandler->addHeader('Location', (string) $url);


			}else{
				return $this->makeDisplay();
			}
		}else{
			return $this->form->makeDisplay();
		}
	}

}

?>