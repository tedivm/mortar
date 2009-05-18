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
 * This validation class checkes to see if the input is an integer.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationDigits extends FormValidationFilter
{
	/**
	 * This is the default error message if the input doesn't validate.
	 *
	 * @var string
	 */
	protected $error = 'Invalid email address';

	/**
	 * This defines the PHP filter that the input is going to be tested against
	 *
	 * @var int
	 */
	protected $filter = FILTER_VALIDATE_INT;
}

?>