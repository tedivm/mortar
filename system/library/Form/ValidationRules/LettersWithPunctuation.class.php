<?php

class FormValidationLettersWithPunctuation extends FormValidationRegex
{
	protected $regex = '/^[a-z-.,()\'"\s]+$/i';
	protected $error = '';
}