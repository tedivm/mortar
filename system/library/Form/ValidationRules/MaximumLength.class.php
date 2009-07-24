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
 * This validation class checked to see if the input less than a certain length.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationMaximumLength extends FormValidationAbstract
{
	/**
	 * This is the default error message if the input doesn't validate.
	 *
	 * @var string
	 */
	protected $error = 'Input was too long';

	/**
	 * This function validates the user input by making sure it is less than the specified length
	 *
	 * @return bool
	 */
	public function validate()
	{
		if($this->value <= $this->argument)
			return true;

		$this->addError('value should be at most ' . $this->argument . 'charactors.');
		return false;
	}
}

?>