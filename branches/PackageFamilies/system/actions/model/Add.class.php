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
class ModelActionAdd extends ModelActionBase
{
	public static $requiredPermission = 'Add';

        public static $settings =  array('Base' => array('headerTitle' => 'Add', 'useRider' => true ));

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
	 * This is the model that called up the action.
	 *
	 * @access protected
	 * @var LocationModel
	 */
	protected $model;

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
			throw new CoreError('Unable to locate ' . $this->model->getType() . ' form');

		$this->form = $form;
		if($this->form->wasSubmitted())
		{
			$inputs = $this->form->checkSubmit();
			if($inputs && $this->formStatus = $this->processInput($inputs))
			{
				CacheControl::clearCache('models', $this->model->getType(), 'browseModelBy');
				$this->formStatus = true;
				$this->onSuccess();
			}else{
				$this->ioHandler->setStatusCode(400);
			}
		}

		$this->setSetting('titleRider', 'Base', ' New ' . $this->model->getType());
	}

	protected function onSuccess()
	{

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
			$baseForm = new $formClassName($formDisplayName, $this->model, $this->actionName);
		}else{
			new CoreInfo('Unable to load ' . $this->model->getType() . ' form ' . $formDisplayName);
		}

		if(!isset($baseForm))
			return false;

		$format = Markup::loadModelEngine($this->model->getType());
		if(isset($format) && $format)
			$baseForm->setMarkup($format);

		return $baseForm;
	}

	static function getFormByType($type)
	{
		$moduleInfo = ModelRegistry::getHandler($type);

		$packageInfo = PackageInfo::loadById($moduleInfo['module']);
		$formClassName = $packageInfo->getClassName('class', $type . 'Form');

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


	protected function processInput($input)
	{
		return $this->form->processInput($input);
	}

	/**
	 * This function handles the view for the html format. If the form was not submitted, or if there is an error, it
	 * gets displayed. Otherwise we redirect the output to the newly saved resource (as a way to prevent the backspace
	 * duplicate issue).
	 *
	 * @return string
	 */
	public function viewHtml($page)
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

				$url = $this->getRedirectUrl();

				//add some sort of message variable so the read page can add a 'you saved' or 'you edited' thing

				$this->ioHandler->addHeader('Location', (string) $url);


			}else{
				return $this->form->getFormAs('Html');
			}
		}else{
			return $this->form->getFormAs('Html');
		}
	}

	public function viewAdmin($page)
	{
		return $this->viewHtml($page);
	}

	public function viewControl($page)
	{
		$url = Query::getUrl();
		$this->form->setAction((string) $url);
		return $this->viewAdmin($page);
	}

	protected function getRedirectUrl()
	{
		$query = Query::getQuery();
		$url = new Url();
		$url->id = $this->model->getId();
		$url->type = $this->model->getType();
		$url->format = $query['format'];
		$url->action = 'Read';
		return $url;
	}

}

?>