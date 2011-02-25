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
 * This validation class checked to see if the input less than a certain length.
 *
 * @package		Library
 * @subpackage	Form
 */
class MortarFormValidationMaximumLength extends MortarFormValidationAbstract
{
	/**
	 * This function validates the user input by making sure it is less than the specified length
	 *
	 * @return bool
	 */
	public function validate()
	{
		if(strlen($this->value) <= $this->argument)
			return true;

		$this->addError('Please enter no more than ' . $this->argument . ' characters.');
		return false;
	}
}

?>