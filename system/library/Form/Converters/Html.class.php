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

		$jsIncludes = array();
		$jsStartup = array();

		foreach($this->inputs as $section => $inputs)
		{
			$sectionHtml = new HtmlObject('fieldset');
			$sectionHtml->property('id', $this->name . "_section_" . $section);

			if(isset($this->sectionLegends[$section]))
			{
				$sectionHtml->insertNewHtmlObject('legend')->
					wrapAround($this->sectionLegends[$section]);
			}

			if(isset($this->sectionIntro[$section]))
			{
				$sectionHtml->insertNewHtmlObject('div')->
					wrapAround($this->sectionIntro[$section])->
					addClass('intro');
			}

			foreach($inputs as $input)
			{
				$inputId = $formId . "_" . $input->name;
				$input->property('id', $inputId);
				$inputJavascript = $this->getInputJavascript($input);

				if(is_array($inputJavascript['startup']))
					$jsStartup = array_merge_recursive($jsStartup, $inputJavascript['startup']);

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

		$jsStartup[] = '$("#' . $this->name . '").validate();';
		$jsStartup[] = '$("#' . $this->name . ' label").tooltip({extraClass: "formTip"});';

		// if the form was submitted, trigger the errors on reload
		if($this->form->wasSubmitted())
			$jsStartup[] = '$(\'#' . $this->name . '\').valid();';

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
		$tagByType = array(
		'html' => 'textarea',
		'textarea' => 'textarea',
		'select' => 'select',
		'checkbox' => 'input',
		'submit' => 'input',
		'radio' => 'input',
		'hidden' => 'input',
		'image' => 'input',
		'text' => 'input',
		'password' => 'input',
		'input' => 'input'
		);

		$tagType = isset($tagByType[$input->type]) ? $tagByType[$input->type] : 'input';
		$inputHtml = new HtmlObject($tagByType[$tagType]);
		$inputHtml->property('name', $input->name);

		if($tagByType[$input->type] == 'input');
		{
			$inputHtml->property('type', $input->type);
		}

		switch ($input->type)
		{
			case'html':
			case 'textarea':
				$inputHtml->wrapAround($input->property('value'));
				unset($input->properties['value']);
				break;

			case 'select':

				$value = $input->property('value');

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
			// If only one checkbox item exists with a single name, we'll take care of it in 'checkSubmit'
			case 'checkbox':
				$inputHtml->property('name', $input->name . '[]');
				break;

			case 'submit':
				$this->submitButton = true;
		}//switch ($input->type)

		$inputHtml->property($input->properties);
		$validationRules = $input->getRules();

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
//exit();
			$validationClasses = json_encode(array('validation' => $validationClientSideRules));
			$inputHtml->addClass($validationClasses);
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

		// to require a javascript file, return $include['Library'][] = 'Name';
		$includes = $startup = $plugin = array();

		switch ($input->type) {
			case 'html':
				$fckOptions = (is_array($input->property('options'))) ? json_encode($input->property('options')) : '';
				$includes['jquery'] = array('FCKEditor');
				$startup[] = '$(\'textarea#' . $input->property('id') . '\').fck(' . $fckOptions . ');';
				break;

			default:
				break;
		}

		if(count($plugin) > 0 || count($startup) > 0)
			return array('includes' => $includes, 'startup' => $startup);

		return false;
	}

}

?>