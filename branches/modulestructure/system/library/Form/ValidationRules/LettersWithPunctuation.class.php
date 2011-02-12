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
class FormValidationLettersWithPunctuation extends FormValidationRegex
{
	/**
	 * This regular expression make sure only letters and some punctuation are present.
	 *
	 * @var string
	 */
	protected $regex = '/^[a-z-.,()\'"\s]+$/i';

	/**
	 * This is the default error message if the input doesn't validate.
	 *
	 * @var string
	 */
	protected $error = 'Please use letters, spaces, and basic punctuation (quotes, parentheses, dash, period, or comma) only.';
}

?>