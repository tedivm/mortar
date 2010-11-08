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
 * This validation class checked to see if the input is at least a certain value.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationMinimumValue extends FormValidationAbstract
{
	/**
	 * Makes sure the value is at least the specified value.
	 *
	 * @return unknown
	 */
	public function validate()
	{
		if($this->value >= $this->argument)
			return true;

		$this->addError('Please enter a value greater than or equal to ' . $this->argument . '.');
		return false;
	}
}

?>