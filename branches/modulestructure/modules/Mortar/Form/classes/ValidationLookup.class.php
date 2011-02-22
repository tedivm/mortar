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
 * This class retrieves class names, and makes sure they're loaded in the system.
 *
 * @package		Library
 * @subpackage	Form
 */
class MortarFormValidationLookup
{
	/**
	 * This is a list of validators in the Form/ValidationRules folder, with the shortname for looking them up.
	 *
	 * @var array
	 */
	static protected $validators = array('required' => 'Required',
									'minlength' => 'MinimumLength',
									'maxlength' => 'MaximumLength',
									'min' => 'MinimumValue',
									'max' => 'MaximumValue',
									'minWords' => 'MinimumWords',
									'maxWords' => 'MaximumWords',
									'email' => 'Email',
									'equalto' => 'EqualTo',
									'url' => 'Url',
									'number' => 'Number',
									'digits' => 'Digits',
									'letterswithbasicpunc' => 'LettersWithPunctuation',
									'alphanumeric' => 'AlphaNumeric',
									'alphanumericpunc' => 'AlphaNumericPunc',
									'lettersonly' => 'LettersOnly',
									'nowhitespace' => 'NoWhiteSpace');

	/**
	 * This function makes sure a class is loaded into the system and returns its name.
	 *
	 * @param string $validationRule
	 * @return string
	 */
	static public function getClass($validationRule)
	{
		$validationRule = strtolower($validationRule);
		if(!isset(self::$validators[$validationRule]))
			return false;

		$classname = 'MortarFormValidation' . self::$validators[$validationRule];

		if(!class_exists($classname, false)) {
			return false;
		} else {
			return true;
		}

		return $classname;
	}

}

?>