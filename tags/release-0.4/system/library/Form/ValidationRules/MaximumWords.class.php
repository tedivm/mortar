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
 * This validation class checked to see if the input has at most so many words.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationMaximumWords extends FormValidationAbstract
{
	/**
	 * This function makes sure the value has no more than the requested number of words.
	 *
	 * @return bool
	 */
	public function validate()
	{
		if(str_word_count($this->value) <= $this->argument)
			return true;

		$this->addError('Input should contain no more than ' . $this->argument . ' words.');
		return false;
	}
}

?>