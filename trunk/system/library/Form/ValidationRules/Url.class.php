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
 * This validation class checked to see if the input is a valid url.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationUrl extends FormValidationFilter
{
	/**
	 * This is the default error message if the input doesn't validate.
	 *
	 * @var string
	 */
	protected $error = 'Invalid url';

	/**
	 * This filter specifies that the input should be a valid url.
	 *
	 * @var int
	 */
	protected $filter = FILTER_VALIDATE_URL;

}

?>