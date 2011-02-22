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
 * This validation class checkes to see if the input is an integer.
 *
 * @package		Library
 * @subpackage	Form
 */
class MortarFormValidationDigits extends MortarFormValidationFilter
{
	/**
	 * This is the default error message if the input doesn't validate.
	 *
	 * @var string
	 */
	protected $error = 'Please enter only digits';

	/**
	 * This defines the PHP filter that the input is going to be tested against
	 *
	 * @var int
	 */
	protected $filter = FILTER_VALIDATE_INT;
}

?>