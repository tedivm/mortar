<?php

if(!class_exists('ValidationLookup', false))
	include('ValidationLookup.class.php');

class FormInput
{
	public $name;
	public $label;
	public $properties = array();
	public $type = 'input';
	//public $value;
	public $options;

	protected $required = false;
	protected $form;
	protected $validationRules = array();
	protected $validationMessages = array();

	protected $filters = array();

	public function __construct($name)
	{
		$this->name = $name;
	}


	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}

	public function setLabel($label)
	{
		$this->label = $label;
		return $this;
	}

	public function getLabel()
	{
		return $this->label;
	}

	public function setValue($value)
	{
		$this->property('value', $value);
		return $this;
	}

	public function attachToForm($form)
	{
		if($form instanceof Form && $form->attachInput($this))
		{
			$this->form = $form;
		}
		return $this;
	}

	public function isRequired($bool = '')
	{
		$this->required = ($bool === false);
		return $this;
	}

	public function getForm()
	{
		return $this->form;
	}

	public function property($property, $value = null)
	{
		if(is_array($property))
		{
			foreach($property as $name => $value)
			{
				$this->properties[$name] = (!is_null($value)) ? $value : false;
			}
			return $this;

		}elseif($value !== null){

			$this->properties[$property] = $value;
			return $this;
		}

		return (isset($this->properties[$property])) ? $this->properties[$property] : null;
	}

	public function addRule($rule, $params = false, $errorMessage = false)
	{
		$this->validationRules[$rule] = (!$params) ? true : $params;
		if($errorMessage !== false)
			$this->validationMessages[$rule] = $errorMessage;

		return $this;
	}

	public function getRules()
	{
		if(count($this->validationRules) > 0)
		{
			$array = $this->validationRules;

			if(count($this->validationMessages) > 1)
			{
				$array['messages'] = $this->validationMessages;
			}
			return $array;
		}
		return array();
	}

	public function validate($value = null)
	{
		$success = true;
		$errors = array();
		foreach($this->validationRules as $rule => $argument)
		{
			$classname = ValidationLookup::getClass($rule);

			$validationRule = new $classname();
			$validationRule->attachInput($this, $value, $argument);

			if(!$validationRule->validate())
			{
				$success = false;

				// getErrors returns false when there are no errors
				if($returnErrors = $validationRule->getErrors())
					$errors = array_merge($errors, $returnErrors);
			}
		} // foreach($this->validationRules as $rule => $argument)

		if(count($errors) > 0)
			$this->validationMessages = $errors;

		return $success;
	}

	public function filter($userInput)
	{
		foreach($this->filters as $filter)
		{
			if(!($filter instanceof $filter))
			{
				if($filterClass = importClass($filter, 'Filters', 'library'))
				{
					$filter = new $filterClass();
				}
			}

			$userInput = $filter->filter($userInput);
		}
		return $userInput;
	}

	public function addFilter($filter)
	{
		$this->filters[] = $filter;
		return $this;
	}

	public function clearFilters()
	{
		$this->filters = array();
	}

	public function check($isChecked)
	{
		if($isChecked == true)
		{
			$this->property('checked', 'checked');
		}elseif($isChecked === false && isset($this->properties['checked'])){
			unset($this->properties['checked']);
		}
		return $this;
	}

	public function setOptions($value, $label = false, $properties = false)
	{
		if(is_array($value))
		{
			$this->options = array_merge($this->options, $value);
		}else{
			$option['value'] = $value;
			$option['label'] = ($label) ? $label : $value;
			$option['properties'] = (is_array($properties) && count($properties)) ? $properties : false;
			$this->options[] = $option;
		}

		return $this;
	}
}


?>