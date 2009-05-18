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
 * This validation class checked to see if the input at most a certain value.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationMaximumValue extends FormValidationAbstract
{
	/**
	 * This is the default error message if the input doesn't validate.
	 *
	 * @var string
	 */
	protected $error = 'Input was too high';

	/**
	 * This make sure the input is less than or equal to the specified value.
	 *
	 * @return bool
	 */
	public function validate()
	{
		if($this->value <= $this->argument)
			return true;

		$this->addError('value should be at least ' . $this->argument . 'charactors.');
		return false;
	}
}

?>