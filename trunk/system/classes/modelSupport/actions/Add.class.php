<?php

class ModelActionAdd extends FormActionBase
{
	protected $parentModel;
	protected $model;
	protected $type;

	public function __construct($argument, $handler)
	{
		if(!($argument instanceof Model)){
			throw new BentoError('No model defined in action.');
		}

		parent::__construct($argument, $handler);
		$this->model = $this->argument;
		$this->type = $this->model->getType();
		unset($this->argument);
	}

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
				$this->formStatus = false;
				$this->ioHandler->setStatusCode(400);
			}
		}
	}

	protected function getForm()
	{
		$query = Query::getQuery();
		$formName = $this->type . 'Form';
		$formClassName = importFromModule($formName, $this->model->getModule(), 'class', true);
		//var_dump($formName);
		//var_dump($this->model->getModule());
		//var_dump($formClassName);
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
//			$locationFormName = importClass('LocationForm', 'modelSupport/Forms/', 'class', true);
//			$locationForm = new $locationFormName();
//			$form->merge($locationForm);
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


	public function viewAdmin()
	{
		return $this->form->makeDisplay();
	}

}

?>