<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package		Library
 * @subpackage	Form
 */

/**
 * This class takes Form objects and converts them to Html. This includes priming the active Page object with any
 * javascript required by the various inputs, such as validation and autosuggest tools.
 *
 * @package		Library
 * @subpackage	Form
 */
class FormToHtml
{
	protected $submitButton = false;
	protected $form;
	protected $name;
	protected $inputs;
	protected $sectionIntro;
	protected $sectionLegends;


	protected $tagByType = array('html' => 'textarea',
							'textarea' => 'textarea',
							'select' => 'select',
							'checkbox' => 'input',
							'submit' => 'input',
							'radio' => 'input',
							'hidden' => 'input',
							'image' => 'input',
							'text' => 'input',
							'password' => 'input',
							'input' => 'input');



	/**
	 * This contructor takes in a Form object and extracts the information needed from it to create the output.
	 *
	 * @param Form $form
	 */
	public function __construct($form)
	{
		$formPackage = $form->getMergePackage();
		$this->name = $form->getName();
		$this->form = $form;
		$this->inputs = $formPackage['inputs'];
		$this->sectionIntro = $formPackage['intros'];
		$this->sectionLegends = $formPackage['legends'];
	}

	/**
	 * This function prepares the Form class by converting it, and all of its inputs, into Html and preparing the
	 * javascript needed to make sure the user sends back what is needed.
	 *
	 * @return string
	 */
	public function makeOutput()
	{
		$formId = $this->name;

		$formHtml = new HtmlObject('form');
		$formHtml->property('method', $this->form->getMethod())->
					property('id', $formId)->
					property('action', $this->form->getAction());

		$jsStartup = array();

		foreach($this->inputs as $section => $inputs)
		{
			$sectionHtml = new HtmlObject('fieldset');
			$sectionHtml->property('id', $formId . "_section_" . $section);

			if(isset($this->sectionLegends[$section]))
				$sectionHtml->insertNewHtmlObject('legend')->
					wrapAround($this->sectionLegends[$section]);

			if(isset($this->sectionIntro[$section]))
				$sectionHtml->insertNewHtmlObject('div')->
					wrapAround($this->sectionIntro[$section])->
					addClass('intro');

			foreach($inputs as $input)
			{
				$inputId = $formId . "_" . $input->name;
				$input->property('id', $inputId);

				if($inputStartupJs = $this->getInputJavascript($input))
					$jsStartup = array_merge_recursive($jsStartup, $inputStartupJs);

				$inputHtml = $this->getInputHtmlByType($input);

				if($input->type == 'hidden')
				{
					$inputHtml->noClose();
					$formHtml->wrapAround($inputHtml);
				}else{

					$labelHtml = new HtmlObject('label');
					$labelHtml->property('for', $inputId)->
						property('id', $inputId . '_label');

					if(isset($input->label))
					{
						$labelHtml->wrapAround($input->label);
						if(isset($input->description))
							$labelHtml->property('title', $input->description);
					}

					$br = new HtmlObject('br');
					$br->noClose();

					$sectionHtml->wrapAround($labelHtml)->
						wrapAround($inputHtml)->
						insertNewHtmlObject('br');
				}
			}//foreach($this->inputs as $section => $inputs)

			$formHtml->wrapAround($sectionHtml);
		}

		if(!$this->submitButton)
		{
			$sectionHtml = new HtmlObject('div');
			$sectionHtml->property('id', $this->name . "_section_" . 'control');
			$inputHtml = new HtmlObject('input');
			$inputHtml->name = $input->name;
			$inputHtml->property('name', 'Submit')->property('type', 'Submit')->property('value', 'Submit');

			$labelHtml = new HtmlObject('label');
			$sectionHtml->wrapAround($labelHtml)->wrapAround($inputHtml)->wrapAround('<br>');
			$formHtml->wrapAround($sectionHtml);
		}

		$formHtml = (string) $formHtml;

		$output = $formHtml;

		$formJsOptions = array();
		$formJsOptions['validateOnLoad'] = $this->form->wasSubmitted();
		$jsStartup[] = '$("#' . $this->name . '").MorterForm(' . json_encode($formJsOptions) . ');';

		if(class_exists('ActivePage', false))
		{
			$page = ActivePage::getInstance();
			$page->addStartupScript($jsStartup);
		}
		return $output;
	}

	/**
	 * This function acts a preprocessor for special input types. Currently its empty, as the last special types were
	 * depreciated out, but that could change.
	 *
	 * @param FormInput $input
	 */
	protected function processSpecialInputFields(FormInput $input)
	{

	}

	/**
	 * Takes an input item and outputs html.
	 *
	 * @param FormInput $input
	 * @return string
	 */
	protected function getInputHtmlByType(FormInput $input)
	{
		// Do a lookup to see what kind of html tag the input needs.
		$tagType = isset($this->tagByType[$input->type]) ? $this->tagByType[$input->type] : 'input';
		$inputHtml = new HtmlObject($tagType);
		$inputHtml->property('name', $input->name);

		// If its a generic input, define the type
		if($tagType == 'input' && $input->type !== 'input')
			$inputHtml->property('type', $input->type);

		$properties = $input->properties;

		// Add the tag specific data to the html. This includes setting the name and value.
		switch ($input->type)
		{
			case 'password':
				unset($properties['value']);
				break;

			case 'html':
			case 'textarea':
				$inputHtml->wrapAround($properties['value']);
				unset($properties['value']);
				break;

			case 'select':
				$value = $properties['value'];
				foreach($input->options as $option)
				{
					$properties = array();

					if($option['value'] == $value)
						$properties['selected'] = 'selected';

					$optionHtml = $inputHtml->insertNewHtmlObject('option')->
						property('value', $option['value'])->
						wrapAround($option['label']);

						if(isset($properties) && is_array($option['properties']))
							$properties = array_merge($properties, $option['properties']);

						$optionHtml->property($properties);
				}
				break;

			// Checkboxes need to be arrays if they have multiple items, but we'll just make them all arrays
			// If only one checkbox item exists with a single name, we'll take care of turning it to a scalar
			// in the Form class's 'checkSubmit' function
			case 'checkbox':
				$inputHtml->property('name', $input->name . '[]');
				break;

			// Here we're just making down that the form has a submit button, otherwise we're going to add our own.
			case 'submit':
				$this->submitButton = true;
		}//switch ($input->type)

		// Set all of the input properties (since the HtmlObject class can take an entire array).
		$inputHtml->property($properties);
		$inputHtml = $this->setInputHtmlMetaData($input, $inputHtml);
		return $inputHtml;
	}

	/**
	 * This function takes certain data from the input column and passes it through the HtmlObject to the client end.
	 * This is done by taking the information as a json encoded array, which our jquery form wrapper picks up.
	 *
	 * @param FormInput $input
	 * @param HtmlObject $inputHtml
	 * @return HtmlObject
	 */
	protected function setInputHtmlMetaData(FormInput $input, HtmlObject $inputHtml)
	{
		$validationRules = $input->getRules();
		$validationClientSideRules = array();
		if(!is_null($validationRules) && count($validationRules) > 0)
		{
			$validationClientSideRules = array();
			foreach($validationRules as $ruleName => $ruleArgument)
			{
				try
				{
					if(!($className = FormValidationLookup::getClass($ruleName)))
						throw new FormWarning('Unable to load validation class for rule ' . $ruleName);

					$argument = staticFunctionHack($className, 'getHtmlArgument', $input, $ruleArgument);
					$validationClientSideRules[$ruleName] = $argument;

				}catch(Exception $e){

				}
			}

			if(count($validationClientSideRules) > 0)
				$inputOptions['validation'] = $validationClientSideRules;

			if($input->type == 'html')
				$inputOptions['html'] = true;


			if(count($inputOptions > 0))
			{
				$metaDataClass = json_encode($inputOptions);
				$inputHtml->addClass($metaDataClass);
			}
		}
		return $inputHtml;
	}

	/**
	 * This function takes in a FormInput and returns any javascript that needs to be included or put into the startup
	 * script.
	 *
	 * @param FormInput $input
	 * @return bool
	 */
	protected function getInputJavascript(FormInput $input)
	{
		return false;
	}

}

?>