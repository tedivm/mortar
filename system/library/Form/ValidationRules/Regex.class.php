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
 * This validation class checkes to see if the input matches the regular expression passed to it as an argument.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationRegex extends FormValidationAbstract
{
	/**
	 * This is here mostly for inheriting classes. If this is set it will be used instead of the passed argument.
	 *
	 * @var string regular expression.
	 */
	protected $regex;

	/**
	 * Checks to see that the input matches the regular expression passed as an argument of the form class.
	 *
	 * @return bool
	 */
	public function validate()
	{
		if(!isset($this->regex))
			$this->regex = $this->argument;

		if(preg_match($this->regex, $this->value) > 0)
			return true;

		$this->addError();
		return false;
	}
}

?>