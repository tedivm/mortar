<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package		Library
 * @subpackage	Form
 */

/**
 * This validation class checked to see if the input matches its paired input.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationEqualTo extends FormValidationAbstract
{
	/**
	 * This is the default error message if the input doesn't validate.
	 *
	 * @var string
	 */
	protected $error = 'Inputs does not match.';

	/**
	 * This function ensures that the user input matches that of the specified input.
	 *
	 * @return bool
	 */
	public function validate()
	{
		$inputHandler = $this->form->getInputHandler();

		if($inputHandler[$this->argument] == $this->value)
			return true;

		$this->addError();
		return false;
	}

	/**
	 * This function allows inheriting classes to alter the argument before it is passed to the html form.
	 *
	 * @param FormInput $input
	 * @param mixed $argument
	 * @return mixed
	 */
	static public function getHtmlArgument(FormInput $input, $argument)
	{
		$form = $input->getForm();
		$argument = '#' . $form->getName() . '_' . $argument;
		return $argument;
	}


}

?>