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
 * This validation class checkes to see if the input is alphanumeric.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationAlphaNumeric extends FormValidationRegex
{
	/**
	 * This regular expression matches nonalphanumeric charactors.
	 *
	 * @var string
	 */
	protected $regex = '/^\w+$/i';

	/**
	 * This is the default error message if the input doesn't validate.
	 *
	 * @var string
	 */
	protected $error = '';
}

?>