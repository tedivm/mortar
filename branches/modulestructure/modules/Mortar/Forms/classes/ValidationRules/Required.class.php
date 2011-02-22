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
 * This validation class checked to see if the input is not defined.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationRequired extends FormValidationAbstract
{
	/**
	 * This is the default error message if the input doesn't validate.
	 *
	 * @var string
	 */
	protected $error = 'This field is required.';

	/**
	 * This function ensures that the user input was set.
	 *
	 * @return bool
	 */
	public function validate()
	{
		if(isset($this->value) && ($this->value || is_numeric($this->value) || $this->value === false))
			return true;

		$this->addError();
		return false;
	}
}

?>