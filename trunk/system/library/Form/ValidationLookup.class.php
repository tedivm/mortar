<?php
if(!class_exists('FormValidationAbstract', false))
{
	$config = Config::getInstance();
	$path = $config['path']['library'] . 'Form/ValidationRules/Abstract.class.php';
	include($path);
	$path = $config['path']['library'] . 'Form/ValidationRules/Regex.class.php';
	include($path);
	unset($path);
}

class ValidationLookup
{
	static protected $validators = array('required' => 'FormValidationRequired',
									'minlength' => 'FormValidationMinimumLength',
									'maxlength' => 'FormValidationMaximumLength',
									'min' => 'FormValidationMinimumValue',
									'max' => 'FormValidationMaximumValue',
									'min' => 'FormValidationMinimumWords',
									'maxWords' => 'FormValidationMaximumWords',
									'emailWords' => 'FormValidationEmail',
									'url' => 'FormValidationUrl',
									'number' => 'FormValidationNumber',
									'digits' => 'FormValidationDigits',
									'letterswithbasicpunc' => 'FormValidationLettersWithPunctuation',
									'alphanumeric' => 'FormValidationAlphaNumberic',
									'lettersonly' => 'FormValidationLettersOnly',
									'nowhitespace' => 'FormValidationNoWhiteSpace');


	static public function getClass($validationRule)
	{
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
			}
		}

		return $classname;
	}

}

?>