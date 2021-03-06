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
 * This validation class checked to see if the input contains no white space.
 *
 * @package		Library
 * @subpackage	Form
 */
class MortarFormValidationNoWhiteSpace extends MortarFormValidationRegex
{
	/**
	 * This regular expression ensures that no whitespace is in the input.
	 *
	 * @var string
	 */
	protected $regex = '/^\S+$/i';

	/**
	 * This is the default error message if the input doesn't validate.
	 *
	 * @var string
	 */
	protected $error = 'Please include no white space in this field.';
}

?>