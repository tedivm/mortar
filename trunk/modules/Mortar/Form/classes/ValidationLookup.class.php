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
	$path = $config['path']['modules'] . 'Mortar/Form/classes/ValidationRules/Abstract.class.php';
	include($path);


	$path = $config['path']['modules'] . 'Mortar/Form/classes/ValidationRules/Filter.class.php';
	include($path);


	$path = $config['path']['modules'] . 'Mortar/Form/classes/ValidationRules/Regex.class.php';
	include($path);
	unset($path);
}


/**
 * This class retrieves class names, and makes sure they're loaded in the system.
 *
 * @package	     Library
 * @subpackage  Form
 */
class MortarFormValidationLookup
{
	/**
	 * This is a list of validators in the Form/ValidationRules folder, with the shortname for looking them up.
	 *
	 * @var array
	 */
	static protected $validators = array('required' => 'MortarFormValidationRequired',
									'minlength' => 'MortarFormValidationMinimumLength',
									'maxlength' => 'MortarFormValidationMaximumLength',
									'min' => 'MortarFormValidationMinimumValue',
									'max' => 'MortarFormValidationMaximumValue',
									'minWords' => 'MortarFormValidationMinimumWords',
									'maxWords' => 'MortarFormValidationMaximumWords',
									'email' => 'MortarFormValidationEmail',
									'equalto' => 'MortarFormValidationEqualTo',
									'url' => 'MortarFormValidationUrl',
									'number' => 'MortarFormValidationNumber',
									'digits' => 'MortarFormValidationDigits',
									'letterswithbasicpunc' => 'MortarFormValidationLettersWithPunctuation',
									'alphanumeric' => 'MortarFormValidationAlphaNumeric',
									'alphanumericpunc' => 'MortarFormValidationAlphaNumericPunc',
									'lettersonly' => 'MortarFormValidationLettersOnly',
									'nowhitespace' => 'MortarFormValidationNoWhiteSpace');


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
			if(strpos($classname, 'MortarFormValidation') == 0)
			{
				$config = Config::getInstance();


				$filename = substr($classname, strlen('MortarFormValidation')) . '.class.php';
				$path = $config['path']['modules'] . 'Mortar/Form/classes/ValidationRules/' . $filename;


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