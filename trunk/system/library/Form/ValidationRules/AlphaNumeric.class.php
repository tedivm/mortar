<?php

class FormValidationAlphaNumeric extends FormValidationRegex
{
	protected $regex = '/^\w+$/i';
	protected $error = '';
}

?>