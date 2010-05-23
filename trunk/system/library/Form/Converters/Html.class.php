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
	protected $sectionOutro;
	protected $sectionLegends;
	protected $sectionClasses;


	protected $tagByType = array('richtext' => 'textarea',
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
		$this->sectionOutro = $formPackage['outros'];
		$this->sectionLegends = $formPackage['legends'];
		$this->sectionClasses = $formPackage['classes'];  
	}

	/**
	 * This function prepares the Form class by converting it, and all of its inputs, into Html and preparing the
	 * javascript needed to make sure the user sends back what is needed.
	 *
	 * @hook Forms HtmlConvert Base
	 * @hook Forms HtmlConvert *type
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

		foreach($this->inputs as $inputs) {
			foreach($inputs as $input) {
				if($input->type == 'checkbox' && isset($input->properties['value']))
					$inputId .= '_' . $input->properties['value'];
				else
					$inputId = $formId . "_" . $input->name;
				$input->property('id', $inputId);
			}
		}

		foreach($this->inputs as $section => $inputs)
		{
			$sectionHtml = new HtmlObject('fieldset');
			$sectionHtml->property('id', $formId . "_section_" . $section);

			if(isset($this->sectionClasses[$section])) {
				foreach($this->sectionClasses[$section] as $class) {
					$sectionHtml->addClass($class);
				}
			} else {
				$this->sectionClasses[$section] = array();
			}

			if(!in_array('mf-toggle-hide', $this->sectionClasses[$section]) && 
			   !in_array('mf-toggle-none', $this->sectionClasses[$section])) {
				$sectionHtml->addClass('mf-toggle-show');
			}


			if(isset($this->sectionLegends[$section]))
				$sectionHtml->insertNewHtmlObject('legend')->
					wrapAround($this->sectionLegends[$section]);

			$sectionDiv = new HtmlObject('div');
			$sectionDiv->addClass('fieldset_contents')->
				property('id', $formId . "_section_" . $section . '_contents');

			if(isset($this->sectionIntro[$section]))
				$sectionDiv->insertNewHtmlObject('div')->
					wrapAround($this->sectionIntro[$section])->
					addClass('fieldset_intro')->
					property('id', $formId . "_section_" . $section . '_intro');

			$sectionHtml->wrapAround($sectionDiv);

			$hasInputs = false;

			$controlsDiv = new HtmlObject('div');
			$controlsDiv->addClass('fieldset_controls')->
				property('id', $formId . "_section_" . $section . '_controls');

			$sectionDiv->wrapAround($controlsDiv);

			foreach($inputs as $input)
			{
				$inputId = $input->property('id');

				if($input->type === 'richtext') {
					$input->property($this->form->getMarkup(), 'true');
					$input->type = 'textarea';
					$input->addClass('fulltext');
				}

				$plugins = new Hook();
				$plugins->enforceInterface('FormToHtmlHook');
				$plugins->loadPlugins('Forms', 'HtmlConvert', 'Base');
				$plugins->loadPlugins('Forms', 'HtmlConvert', $input->type);
				$plugins->setInput($input);

				$jsStartup = array_merge_recursive($jsStartup,
								Hook::mergeResults($plugins->getCustomJavaScript()));

				if(in_array(true, Hook::mergeResults($plugins->overrideHtml())))
				{
					$plugins->createOverriddingHtml($sectionHtml);
					continue;
				}


				if($inputStartupJs = $this->getInputJavascript($input))
					$jsStartup = array_merge_recursive($jsStartup, $inputStartupJs);

				$inputHtml = $this->getInputHtmlByType($input);
				$plugins->setCustomHtml($inputHtml);

				if($inputOptions = $this->getInputMetaData($input) ) //count($inputOptions > 0))
				{
					$metaDataClass = json_encode($inputOptions);
					$inputHtml->addClass($metaDataClass);
				}


				if($input->type == 'hidden')
				{
					$inputHtml->close(false);
					$formHtml->wrapAround($inputHtml);
				}else{

					$labelHtml = new HtmlObject('label');
					$labelHtml->property('for', $inputId)->
						property('id', $inputId . '_label');

					if(isset($input->pretext))
						$controlsDiv->wrapAround($input->pretext);

					if(isset($input->label))
					{
						$labelHtml->wrapAround($input->label);
						if(isset($input->description))
							$labelHtml->property('title', $input->description);
					}

					$controlsDiv->wrapAround($labelHtml)->
						wrapAround($inputHtml);

					if(isset($input->posttext))
						$controlsDiv->wrapAround($input->posttext);

					if(!isset($input->noBreak) || $input->noBreak === false)
						$controlsDiv->insertNewHtmlObject('br');

					$hasInputs = true;
				}
			}//foreach($this->inputs as $section => $inputs)

			if(isset($this->sectionOutro[$section]))
				$sectionDiv->insertNewHtmlObject('div')->
					wrapAround($this->sectionOutro[$section])->
					addClass('fieldset_outro')->
					property('id', $formId . "_section_" . $section . '_outro');

			if($hasInputs)
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
		$inputHtml->addClass($input->classes);

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
				if((isset($properties['value'])))
				{
					$inputHtml->wrapAround($properties['value']);
					unset($properties['value']);
				}
				break;

			case 'select':

				$value = (isset($properties['value'])) ? $properties['value'] : null;

				foreach($input->options as $option)
				{
					$optionProperties = array();

					if($option['value'] == $value)
						$optionProperties['selected'] = 'selected';

					$optionHtml = $inputHtml->insertNewHtmlObject('option')->
						property('value', $option['value'])->
						wrapAround($option['label']);

						if(isset($optionProperties) && is_array($option['properties']))
							$optionProperties = array_merge($optionProperties, $option['properties']);

						$optionHtml->property($optionProperties);
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

		return $inputHtml;
	}

	/**
	 * This function places meta data used by the client side jQuery code to add various effects, such as user input
	 * validation and ajax autocomplete values.
	 *
	 * @param FormInput $input
	 * @return array|false
	 */
	protected function getInputMetaData(FormInput $input)
	{
		$inputOptions = array();
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
		}

		if($input->property('html'))
			$inputOptions['html'] = true;

		if(isset($input->properties['autocomplete']) && $input->properties['autocomplete'])
		{
			$inputOptions['autocomplete']['data'] = $input->properties['autocomplete'];
			if(isset($input->properties['multiple']))
			{
				$inputOptions['autocomplete']['options']['multiple'] = true;
			}
			unset($input->properties['autocomplete']);
		}

		$plugins = new Hook();
		$plugins->enforceInterface('FormMetadataHook');
		$plugins->loadPlugins('Forms', 'Metadata', 'Base');
		$plugins->loadPlugins('Forms', 'Metadata', $input->type);
		$pluginInput = $plugins->getMetadataOptions($input);

		foreach($pluginInput as $inputItem)
			$inputOptions = array_merge($inputOptions, $inputItem);

		return (count($inputOptions > 0)) ? $inputOptions : false;
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


interface FormToHtmlHook
{
	public function setInput(FormInput $input);
	public function getCustomJavaScript();
	public function overrideHtml();
	public function createOverriddingHtml($sectionHtml);
	public function setCustomHtml($inputHtml);
}

interface FormMetadataHook
{
	public function getMetadataOptions(FormInput $input);
}

?>