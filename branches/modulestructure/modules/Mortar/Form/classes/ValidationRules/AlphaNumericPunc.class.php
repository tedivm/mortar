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
 * This validation class checked to see if the input contains only letters and basic puncuation.
 *
 * @package		Library
 * @subpackage	Form
 */
class MortarFormValidationAlphaNumericPunc extends MortarFormValidationRegex
{
	/**
	 * This regular expression allows letters, numbers, and a range of "safe" punctuation.
	 *
	 * @var string
	 */
	protected $regex = '/^[a-z\d-+.,()\'?!@#$%&"_\s]+$/i';

	/**
	 * This is the default error message if the input doesn't validate.
	 *
	 * @var string
	 */
	protected $error = 'Please use letters, numbers, spaces, and punctuation (+ - . , ( ) \' ? ! @ # $ % & | " _) only.';
}

?>