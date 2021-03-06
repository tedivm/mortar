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
 * This validation class checked to see if the input contains at least so many words.
 *
 * @package		Library
 * @subpackage	Form
 */
class MortarFormValidationMinimumWords extends MortarFormValidationAbstract
{
	/**
	 * This function makes sure the value has no less than the requested number of words.
	 *
	 * @return bool
	 */
	public function validate()
	{
		if(str_word_count($this->value) >= $this->argument)
			return true;

		$this->addError('Please enter at least ' . $this->argument . ' words.');
		return false;
	}
}

?>