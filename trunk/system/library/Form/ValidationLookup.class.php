<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package		Library
 * @subpackage	Form
 */


if(!class_exists('FormValidationAbstract', false))
{
	$config = Config::getInstance();
	$path = $config['path']['library'] . 'Form/ValidationRules/Abstract.class.php';
	include($path);

	$path = $config['path']['library'] . 'Form/ValidationRules/Filter.class.php';
	include($path);

	$path = $config['path']['library'] . 'Form/ValidationRules/Regex.class.php';
	include($path);
	unset($path);
}

/**
 * This class retrieves class names, and makes sure they're loaded in the system.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormValidationLookup
{
	/**
	 * This is a list of validators in the Form/ValidationRules folder, with the shortname for looking them up.
	 *
	 * @var array
	 */
	static protected $validators = array('required' => 'FormValidationRequired',
									'minlength' => 'FormValidationMinimumLength',
									'maxlength' => 'FormValidationMaximumLength',
									'min' => 'FormValidationMinimumValue',
									'max' => 'FormValidationMaximumValue',
									'min' => 'FormValidationMinimumWords',
									'maxWords' => 'FormValidationMaximumWords',
									'email' => 'FormValidationEmail',
									'equalto' => 'FormValidationEqualTo',
									'url' => 'FormValidationUrl',
									'number' => 'FormValidationNumber',
									'digits' => 'FormValidationDigits',
									'letterswithbasicpunc' => 'FormValidationLettersWithPunctuation',
									'alphanumeric' => 'FormValidationAlphaNumeric',
									'lettersonly' => 'FormValidationLettersOnly',
									'nowhitespace' => 'FormValidationNoWhiteSpace');

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

		$classname = self::$validators[$validationRule];

		if(!class_exists($classname, false))
		{
			if(strpos($classname, 'FormValidation') == 0)
			{
				$config = Config::getInstance();

				$filename = substr($classname, strlen('FormValidation')) . '.class.php';
				$path = $config['path']['library'] . 'Form/ValidationRules/' . $filename;

				if(file_exists($path))
				{
					include($path);
				}else{
					return false;
				}
			}else{
				return false;
			}
		}

		return $classname;
	}

}

?>