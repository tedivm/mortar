<?php

class FormValidator
{

	public function validate($rule, $form, $inputName, $params)
	{
		
	}
}


abstract class FormValidatorRule
{
	protected $form;
	protected $message = '';
	protected $handler;
	
	public function __construct($form, $message = false)
	{
		$this->form = $form;
		$this->message = $message;
		$this->handler = $form->getInputHandler;
	}
	
	abstract function validate($inputName, $params = false);
}


abstract class FormValidator_RegEx extends FormValidatorRule
{
	protected $regex;
	
	public function validate($inputName, $params = false)
	{
		$regEx = $this->regex;
		if(!is_array($regEx))
		{
			$regEx = array($regEx);
		}
			
		foreach($regEx as $expression)
		{
			if(!preg_match($expression, $this->handler[$inputName]))
				return false;
		}
			
		return true;
	}	
}






class FormValidator_Required extends FormValidatorRule
{
	public function validate($inputName, $params = false)
	{
		return (isset($this->handler[$inputName]));
	}
}

class FormValidator_MinLength extends FormValidatorRule
{
	public function validate($inputName, $params = false)
	{
		return (strlen($this->handler[$inputName]) >= $param['length']);
	}
}

class FormValidator_MaxLength extends FormValidatorRule
{
	public function validate($inputName, $params = false)
	{
		return (strlen($this->handler[$inputName]) <= $param['length']);
	}
}

class FormValidator_RangeLength extends FormValidatorRule
{
	public function validate($inputName, $params = false)
	{
		return (FormValidator_MaxLength::validate($form, $inputName, array('length' => $params['maxLength']) ) && FormValidator_MinLength::validate($form, $inputName, array('length' => $params['minLength']) ));
	}
}

class FormValidator_MinValue extends FormValidatorRule
{
	public function validate($inputName, $params = false)
	{
		return ($this->handler[$inputName] >= $param['length']);
	}
}

class FormValidator_MaxValue extends FormValidatorRule
{
	public function validate($inputName, $params = false)
	{
		return ($this->handler[$inputName] <= $param['length']);
	}
}

class FormValidator_RangeValue extends FormValidatorRule
{
	public function validate($inputName, $params = false)
	{
		return (FormValidator_MaxValue::validate($form, $inputName, array('length' => $params['maxLength']) ) && FormValidator_MinValue::validate($form, $inputName, array('length' => $params['minLength']) ));
	}
}

class FormValidator_Email extends FormValidatorRule
{
	public function validate($inputName, $params = false)
	{
		$emailValidator = new EmailAddressValidator();
		return $emailValidator->check_email_address($this->handler[$inputName]);
	}
}

class FormValidator_Url extends FormValidator_RegEx
{
	protected $regex = '^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&\'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&\'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&\'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&\'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&\'\(\)\*\+,;=]|:|@)|\/|\?)*)?$';


}

class FormValidator_Date extends FormValidator_RegEx
{
	protected $regex = '\b(0?[1-9]|[12][0-9]|3[01])[- /.](0?[1-9]|[12][0-9]|3[01])[- /.](19|20)?[0-9]{2}\b';	

}

class FormValidator_Number extends FormValidatorRule
{
	public function validate($inputName, $params = false)
	{
		return is_numeric($this->handler[$inputName]);
	}
}

class FormValidator_Digit extends FormValidatorRule // translates to the Digits jQuery method
{
	public function validate($inputName, $params = false)
	{
		return is_int($this->handler[$inputName]);
	}
}

class FormValidator_CreditCard extends FormValidator_RegEx
{
	protected $regex = '^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6011[0-9]{12}|3(?:0[0-5]|[68][0-9])[0-9]{11}|3[47][0-9]{13})$';
}

class FormValidator_MaxWords extends FormValidatorRule
{
	public function validate($inputName, $params = false)
	{
		return (str_word_count($this->handler[$inputName]) <= $params['words']);
	}
}

class FormValidator_MinWords extends FormValidatorRule
{
	public function validate($inputName, $params = false)
	{
		return (str_word_count($this->handler[$inputName]) >= $params['words']);		
	}
}

class FormValidator_RangeWords extends FormValidatorRule
{
	public function validate($inputName, $params = false)
	{
		return (FormValidator_MaxWords::validate($form, $inputName, array('words' => $params['maxWords']) ) && FormValidator_MinWords::validate($form, $inputName, array('words' => $params['minWords']) ));
	}
}




class FormValidator_letterswithbasicpunc
{
	public function validate($inputName, $params = false)
	{
		
	}
}

class FormValidator_alphanumeric
{
	public function validate($inputName, $params = false)
	{
		
	}
}

class FormValidator_lettersonly
{
	public function validate($inputName, $params = false)
	{
		
	}
}

class FormValidator_nowhitespace
{
	public function validate($inputName, $params = false)
	{
		
	}
}



/*
class FormValidator_
{
	public function validate($inputName, $params = false)
	{
		
	}
}

*/


/*
jQuery Validation Methods:

required()
required(dependency-expression)
required(dependency-callback)

remote( url )

minlength( length )
maxlength( length )
rangelength( array(min, max) )


min( value )
max( value )
range( array(min, max) )

email()
url()
date()
number()
digits()
creditcard()

accept( fileExtension )

equalTo( inputSelector )


maxWords( length )
minWords( length )
rangeWords( array(min, max) )

letterswithbasicpunc()
alphanumeric()
lettersonly()
nowhitespace()

vinUS()
phone()

*/

?>