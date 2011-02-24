<?php

abstract class ModelForm extends MortarFormForm
{
	protected $model;
	protected $actionName;
	protected $extensionForm;

	protected $logFields = array();

	public function __construct($name, $model, $actionName)
	{
		$this->model = $model;
		$this->actionName = $actionName;
		parent::__construct($name);
	}

	protected function define()
	{
		$this->createCustomInputs();

		$query = Query::getQuery();
		$formExtension = $this->model->getType() . $query['format'] . 'Form';

		if($formExtensionClassName = importFromModule($formExtension, $this->model->getModule(), 'class'))
		{
			$formatForm = new $formClassName($formDisplayName);
			$this->extensionForm = $formatForm;
			$this->merge($formatForm);
		}else{
			new CoreInfo('Unable to load ' . $this->model->getType() . ' ' . $query['format'] . ' form extension');
		}

		$thisHook = new Hook();
		$thisHook->loadModelPlugins($this->model, 'baseForm');
		$thisHook->loadModelPlugins($this->model, $this->actionName . 'Form');
		$thisHook->adjustForm($this->model, $this);
	}

	protected function createCustomInputs()
	{

	}

	public function populateInputs()
	{
		$inputGroups = $this->getInputGroups(($this->getInputList()));

		if(isset($inputGroups['model'])) foreach($inputGroups['model'] as $name)
		{
			$input = $this->getInput('model_' . $name);

			if($input instanceof MortarFormInput)
			{
				if($input->type == 'richtext') {
					if($value = $this->model['raw' . ucfirst($name)]) {
						$input->setValue($value);
					} else {
						$input->setValue($this->model[$name]);
					}
				} else if($input->type == 'checkbox') {
					if(isset($this->model[$name]) && $this->model[$name])
					{
						$input->check(true);
					}
				}else{
					$input->setValue($this->model[$name]);
				}

			}else{
				//check boxes
			}
		}

		$this->populateCustomInputs();
	}

	protected function populateCustomInputs()
	{
		return true;
	}

	/**
	 * This function seperates out the specific input groups (specified by groupname_) and adds them to the object
	 * that uses them.
	 *
	 * @access protected
	 * @param array $input
	 * @return bool This is the status on whether the model was successfully saved
	 */
	public function processInput($input)
	{
		$user = ActiveUser::getUser();

		$inputNames = array_keys($input);
		$inputGroups = $this->getInputGroups($inputNames);

		foreach($inputGroups['model'] as $name)
		{
			if(in_array($name, $this->logFields) && $this->model[$name] != $input['model_' . $name]) {
				$old = $this->model[$name];
				$new = $input['model_' . $name];
				ChangeLog::logChange($this->model, $name . ' changed', $user, 'Edit', "from '$old' to '$new'");
			}

			$this->model[$name] = $input['model_' . $name];
		}

		if(!$this->processCustomInputs($input))
			return false;

		if(isset($this->extensionForm) && method_exists($this->extensionForm, 'processExtensionInputs'))
			$this->extensionForm->processExtensionInputs($input);

		$this->processPluginInputs($input, false);
		$success = $this->model->save();

		if($success) {
			$success = $this->postProcessCustomInputs($input);
			$this->processPluginInputs($input, true);
		}
		return $success;
	}

	protected function processCustomInputs($input)
	{
		return true;
	}

	protected function postProcessCustomInputs($input)
	{
		return true;
	}

	protected function processPluginInputs($input, $post = false)
	{
		$formHook = new Hook();
		$formHook->loadModelPlugins($this->model, 'baseForm');
		$formHook->loadModelPlugins($this->model, $this->actionName . 'Form');
		if($post) {
			$formHook->processAdjustedInputPost($this->model, $input);
		} else {
			$formHook->processAdjustedInput($this->model, $input);
		}			
	}
}

?>