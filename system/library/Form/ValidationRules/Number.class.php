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
 * This validation class checked to see if the input is a valid number.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationNumber extends FormValidationFilter
{
	/**
	 * This is the default error message if the input doesn't validate.
	 *
	 * @var string
	 */
	protected $error = 'Not a valid number';

	/**
	 * This filter specifies that the input should be a float.
	 *
	 * @var int
	 */
	protected $filter = FILTER_VALIDATE_FLOAT;

}

?>