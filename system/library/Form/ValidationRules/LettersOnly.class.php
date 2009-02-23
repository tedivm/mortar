<?php

class FormValidationLettersOnly extends FormValidationRegex
{
	protected $regex = '/^[a-z]+$/i';
	protected $error = '';
}

?>