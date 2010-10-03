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
 * This validation class checked to see if the input is a valid number.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationNumber extends FormValidationAbstract
{
	/**
	 * This is the default error message if the input doesn't validate.
	 *
	 * @var string
	 */
	protected $error = 'Please enter a valid number.';

	/**
	 * This make sure the input is less than or equal to the specified value.
	 *
	 * @return bool
	 */
	public function validate()
	{
		if(is_numeric($this->value))
			return true;

		return false;
	}
}


?>