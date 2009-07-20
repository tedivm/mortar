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
 * This class is called if a model needs to be created but does not have an action class to do so.
 *
 * @package System
 * @subpackage ModelSupport
 */
class ModelActionAdd extends ModelActionBase
{
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
		$query = Query::getQuery();
		$form = $this->getForm();

		if($form == false)
			throw new BentoError('Unable to locate ' . $this->model->getType() . ' form');

		$this->form = $form;
		if($this->form->wasSubmitted())
		{
			$inputs = $this->form->checkSubmit();
			if($inputs && $this->formStatus = $this->processInput($inputs))
			{
				Cache::clear('models', $this->model->getType(), 'browseModelBy');
				$this->formStatus = true;
			}else{
				$this->ioHandler->setStatusCode(400);
			}
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
		$query = Query::getQuery();

		$formDisplayName = $this->type . 'Form' . $this->actionName;
		if($formClassName = self::getFormByType($this->model->getType()))
		{
			$baseForm = new $formClassName($formDisplayName);
		}else{
			new BentoInfo('Unable to load ' . $this->model->getType() . ' form ' . $formName);
		}

		$formExtension = $this->type . $query['format'] . 'Form';

		if($formExtensionClassName = importFromModule($formExtension, $this->model->getModule(), 'class'))
		{
			$formatForm = new $formClassName($formDisplayName);

			if(isset($baseForm))
			{
				$baseForm->merge($formatForm);
			}else{
				$baseForm = $formatForm;
			}
		}else{
			new BentoInfo('Unable to load ' . $this->model->getType() . ' ' . $query['format'] . ' form extension');
		}

		if(!isset($baseForm))
			return false;

		return $baseForm;
	}

	static function getFormByType($type)
	{
		$moduleInfo = ModelRegistry::getHandler($type);
		$formname = $type . 'Form';
		$formClassName = importFromModule($formname, $moduleInfo['module'], 'class');

		if($formClassName !== false)
		{
			return $formClassName;
		}else{
			$reflection = new ReflectionClass($moduleInfo['class']);
			$parentClass = $reflection->getParentClass();

			if($parentType = $parentClass->getStaticPropertyValue('type'))
			{
				return self::getFormByType($parentType);
			}else{
				return false;
			}
		}
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

		foreach($inputGroups['model'] as $name)
		{
			$this->model[$name] = $input['model_' . $name];
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
				$url = new Url();
				$url->id = $this->model->getId();
				$url->type = $this->model->getType();
				$url->format = 'Admin';
				$url->action = 'Read';

				//add some sort of message variable so the read page can add a 'you saved' or 'you edited' thing

				$this->ioHandler->addHeader('Location', (string) $url);


			}else{
				return 'Unable to add ' . $this->type;
			}
		}else{
			return $this->form->makeHtml();
		}
	}

}

?>