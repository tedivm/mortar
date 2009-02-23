<?php

class FormValidationNoWhiteSpace extends FormValidationRegex
{
	protected $regex = '/^\S+$/i';
	protected $error = '';
}