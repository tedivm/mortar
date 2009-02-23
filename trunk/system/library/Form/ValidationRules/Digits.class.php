<?php

class FormValidationDigits extends FormValidationFilter
{
	protected $error = 'Invalid email address';
	protected $filter = FILTER_VALIDATE_INT;
}

?>