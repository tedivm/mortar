<?php

class FormValidationEmail extends FormValidationFilter
{
	protected $error = 'Invalid email address';
	protected $filter = FILTER_VALIDATE_FLOAT;

}

?>