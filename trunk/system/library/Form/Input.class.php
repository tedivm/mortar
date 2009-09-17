<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package		Library
 * @subpackage	Form
 */

/**
 * This class stores the data for individual inputs in the Form class.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormInput
{
	/**
	 * This is the name of the input
	 *
	 * @var string
	 */
	public $name;

	/**
	 * This is the label text for the input.
	 *
	 * @var string
	 */
	public $label;

	/**
	 * This is a discription or hint about what the input expects.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * This is an array of properties about the input. Examples include the value of the input, although any html tag
	 * can be here.
	 *
	 * @var array
	 */
	public $properties = array();

	/**
	 * This is the type of input.
	 *
	 * @var string
	 */
	public $type = 'input';

	/**
	 * For menus this array is filled with the various options that can be choosen.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * This is a flag to see if the value is required by the form.
	 *
	 * @var bool
	 */
	protected $required = false;

	/**
	 * This is a reference back to the parent form.
	 *
	 * @var Form
	 */
	protected $form;

	/**
	 * This is an array of validation information that is used to validate the input.
	 *
	 * @var array
	 */
	protected $validationRules = array();

	/**
	 * This is an array of error messages to match failed validation checks.
	 *
	 * @var array
	 */
	public $validationMessages = array();

	/**
	 * This is an array of Filters that the input is run through before being returned.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * The constructor takes the name of the input.
	 *
	 * @param string $name
	 */
	public function __construct($name)
	{
		$this->name = $name;
	}

	/**
	 * This function sets the type of input. Returns itself for method chaining.
	 *
	 * @param string $type
	 * @return FormInput
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * This function sets the label of input. Returns itself for method chaining.
	 *
	 * @param string $abel
	 * @return FormInput
	 */
	public function setLabel($label)
	{
		$this->label = $label;
		return $this;
	}

	/**
	 * This function sets the description, a hint on the type of input expected from the user.
	 *
	 * @param string $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
		return $this;
	}

	/**
	 * Returns the input's label.
	 *
	 * @return unknown
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * This function returns the name of the input.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * This function sets the value of input. Returns itself for method chaining.
	 *
	 * @param string $value
	 * @return FormInput
	 */
	public function setValue($value)
	{
		$this->property('value', $value);
		return $this;
	}

	/**
	 * This function attaches the input to a form. Returns itself for the Form counterpart function.
	 *
	 * @param Form $form
	 * @return FormInput
	 */
	public function attachToForm($form)
	{
		if($form instanceof Form && $form->attachInput($this))
		{
			$this->form = $form;
		}
		return $this;
	}

	/**
	 * Sets the input as required. Returns itself for method chaining.
	 *
	 * @param bool $bool
	 * @return FormInput
	 */
	public function isRequired($bool = true)
	{
		$this->required = ($bool === true);
		return $this;
	}

	/**
	 * This function returns the form the input is attached to or false if its not attached to anything.
	 *
	 * @return Form|bool
	 */
	public function getForm()
	{
		return isset($this->form) ? $this->form : false;
	}

	/**
	 * This function sets the value of one of the inputs properties, or it can receive an array to add multiple
	 * properties at once.
	 *
	 * @param string|array $property
	 * @param string|null $value If the first argument is sent an array this argument is discarded.
	 * @return unknown
	 */
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

	/**
	 * Adds a validation rule to the the input. Returns itself for method chaining.
	 *
	 * @param string $rule
	 * @param null|mixed $params The arguments to be passed to the validation class on creation
	 * @param null|string $errorMessage An optional error message if the validation fails
	 * @return FormInput
	 */
	public function addRule($rule, $params = null, $errorMessage = null)
	{
		$this->validationRules[$rule] = isset($params) ? $params : true;
		if(isset($errorMessage))
			$this->validationMessages[$rule] = $errorMessage;

		return $this;
	}

	/**
	 * This function returns the rules for this input as an array.
	 *
	 * @return array
	 */
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

	/**
	 * This function validates a user input against the validation rules.
	 *
	 * @param string|null $value Passing nothing is an option.
	 * @return bool
	 */
	public function validate($value = null)
	{
		$success = true;
		$errors = array();

		if(($value == null || $value == '') && !isset($this->validationRules['required']))
			return true;

		foreach($this->validationRules as $rule => $argument)
		{
			$classname = FormValidationLookup::getClass($rule);

			if($classname === false)
				throw new FormError('Unable to load validation class ' . $rule);

			$validationRule = new $classname();
			$validationRule->attachInput($this, $value, $argument);

			if($validationRule->validate() !== true)
			{
				$success = false;

				// getErrors returns false when there are no errors
				if($returnErrors = $validationRule->getErrors())
					$errors = array_merge($errors, $returnErrors);
			}
		} // foreach($this->validationRules as $rule => $argument)

		if(count($errors) > 0)
		{
			$this->validationMessages = $errors;
			$name = $this->getName();
			foreach($errors as $error)
				new FormValidation($name . $error);
		}

		return $success;
	}

	/**
	 * Filters a user input through an array of filter objects.
	 *
	 * @param mixed $userInput
	 * @return mixed
	 */
	public function filter($userInput)
	{
		foreach($this->filters as $filter)
			$userInput = $filter->filter($userInput);

		return $userInput;
	}

	/**
	 * Adds a filter on the user input. Returns itself for method chaining.
	 *
	 * @param Filter $filter
	 * @return FormInput
	 */
	public function addFilter(Filter $filter)
	{
		$this->filters[] = $filter;
		return $this;
	}

	/**
	 * This clears out all the filters on the input.
	 *
	 */
	public function clearFilters()
	{
		$this->filters = array();
	}

	/**
	 * Checks or unchecks the current input (assuming its a checkbox). Returns itself for method chaining.
	 *
	 * @param bool $isChecked
	 * @return FormInput
	 */
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

	/**
	 * Adds an option, or an array of options, to the form input. These are only used for select-style inputs. Returns
	 * itself for method chaining.
	 *
	 * @param string|array $value Either a value name or an array of arrays (using indexes value, label and properties)
	 * @param string|null $label If value is an array, this is discarded.
	 * @param array|null $properties If value is an array, this is discarded.
	 * @return FormInput
	 */
	public function setOptions($value, $label = null, $properties = null)
	{
		if(is_array($value))
		{
			$this->options = array_merge($this->options, $value);
		}else{
			$option['value'] = $value;
			$option['label'] = isset($label) ? $label : $value;
			$option['properties'] = (isset($properties) && is_array($properties)) ? $properties : false;
			$this->options[] = $option;
		}

		return $this;
	}
}


?>