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
 * This validation class checkes to see if the input contains only letters.
 *
 * @package		Library
 * @subpackage	Form
 */
class MortarFormValidationLettersOnly extends MortarFormValidationRegex
{
	/**
	 * This regular expression makes sure only letters are in the string
	 *
	 * @var string
	 */
	protected $regex = '/^[a-z]+$/i';

	/**
	 * This is the default error message if the input doesn't validate.
	 *
	 * @var string
	 */
	protected $error = 'Please use letters only.';
}

?>