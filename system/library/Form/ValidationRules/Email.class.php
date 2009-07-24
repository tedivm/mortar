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
 * This validation class checked to see if the input is a properly formated email address.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationEmail extends FormValidationFilter
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
	protected $filter = FILTER_VALIDATE_EMAIL;

}

?>