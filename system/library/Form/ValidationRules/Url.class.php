<?php

class FormValidationUrl extends FormValidationFilter
{
	protected $error = 'Invalid url';
	protected $filter = FILTER_VALIDATE_URL;

}

?>