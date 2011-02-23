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
 * This abstract class serves as the starting point for building FormValidation classes.
 *
 * @package		Library
 * @subpackage	Form
 */
abstract class MortarFormValidationAbstract
{
	/**
	 * This is an array of errors that occured during validation.
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * This is a reference to the input being validated
	 *
	 * @var MortarFormInput
	 */
	protected $input;

	/**
	 * This is any argument passed by the form.
	 *
	 * @var null|mixed
	 */
	protected $argument;

	/**
	 * This is a copy of the value being tested. It is possible for this value to be null.
	 *
	 * @var mixed|null
	 */
	protected $value;

	/**
	 * This is a reference to the form the input belongs to.
	 *
	 * @var MortarFormInput
	 */
	protected $form;

	/**
	 * This can be defined as the default error by children classes.
	 *
	 * @var unknown_type
	 */
	protected $error;


	/**
	 * This function is used to attach an input to the class for validation.
	 *
	 * @param MortarFormInput $input
	 * @param mixed $userValue This is the value to be tested.
	 * @param mixed $argument
	 */
	public function attachInput(MortarFormInput $input, $userValue = null, $argument = null)
	{
		$this->input = $input;
		$this->argument = $argument;
		$this->form = $input->getForm();
		$this->value = isset($userValue) ? $userValue : null;
	}

	/**
	 * This function should be defined by the inheriting class. It should return true for successful validations and
	 * false on failure.
	 *
	 * @return bool
	 */
	abstract public function validate();

	/**
	 * This function allows inheriting classes to alter the argument before it is passed to the html form.
	 *
	 * @param MortarFormInput $input
	 * @param mixed $argument
	 * @return mixed
	 */
	static public function getHtmlArgument(MortarFormInput $input, $argument)
	{
		return $argument;
	}

	/**
	 * This returns an array of errors, or false if there are none.
	 *
	 * @return array|bool
	 */
	public function getErrors()
	{
		if(count($this->errors) < 1)
			return false;

		return $this->errors;
	}

	/**
	 * This function adds an error to the error array.
	 *
	 * @param unknown_type $errorText
	 */
	protected function addError($errorText = null)
	{
		if(is_null($errorText))
			$errorText = $this->error;

		$this->errors[] = $errorText;
	}

}

?>