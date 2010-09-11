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
 * This validation class checked to see if the input at most a certain value.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationMaximumValue extends FormValidationAbstract
{
	/**
	 * This make sure the input is less than or equal to the specified value.
	 *
	 * @return bool
	 */
	public function validate()
	{
		if($this->value <= $this->argument)
			return true;

		$this->addError('Please enter a value less than or equal to ' . $this->argument . '.');
		return false;
	}
}

?>