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
 * This validation class checked to see if the input is at least a certain length.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationMinimumLength extends FormValidationAbstract
{
	/**
	 * Makes sure the input is at least the specified value.
	 *
	 * @return unknown
	 */
	public function validate()
	{
		if(strlen($this->value) >= $this->argument)
			return true;

		$this->addError('Please enter at least ' . $this->argument . ' characters.');
		return false;
	}
}

?>