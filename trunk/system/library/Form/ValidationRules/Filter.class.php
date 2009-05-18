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
 * This abstract class uses the php filter functions to validate user inputs against form requirements.
 *
 * @package		Library
 * @subpackage	Form
 */
abstract class FormValidationFilter extends FormValidationAbstract
{
	/**
	 * This should be an int, corresponding with the php filter constants.
	 *
	 * @link http://us.php.net/manual/en/filter.constants.php
	 * @var int
	 */
	protected $filter;

	/**
	 * This function uses the php filter_var function to see if the user input is as it should be.
	 *
	 * @return bool
	 */
	public function validate()
	{
		if(!isset($this->filter))
			$this->filter = $this->argument;

		if(filter_var($this->value, $this->filter))
			return true;

		$this->addError();
		return false;
	}
}


?>