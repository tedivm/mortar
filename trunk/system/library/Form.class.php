<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package		Library
 * @subpackage	Form
 */


require('Form/Input.class.php');

/**
 * This is used to generate forms. This is different than just HTML forms (although that is an option for output), as
 * this class is used to define which inputs should be accepted and what rules they need to follow regardless of engine.
 *
 * @package		Library
 * @subpackage	Form
 */
class Form
{
	/**
	 * This is the name of the form, used to generate unique ids for each element.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * This stores the current input being used for this form. If this isn't set, it defaults to the output of the
	 * Input::getInput() static function.
	 *
	 * @var array
	 */
	public $userInput;

	/**
	 * This string identifies the current active section being worked on.
	 *
	 * @var string
	 */
	protected $activeSection = 'main';

	/**
	 * This is an array of an araay of input objects. The index of the first part of the array corresponds to the
	 * input's section
	 *
	 * @var array
	 */
	protected $inputs = array();

	/**
	 * This is the location the form sends to, if its generated as html.
	 *
	 * @var string
	 */
	protected $action = false;

	/**
	 * This is an array of legend text to use for each section of the form.
	 *
	 * @var array
	 */
	protected $sectionLegends = array();

	/**
	 * This is an array of introduction text for each section.
	 *
	 * @var array
	 */
	protected $sectionIntro = array();

	/**
	 * This is the method used to send forms back from html. It defaults to post, get being the other reasonable item.
	 *
	 * @var string post or get
	 */
	protected $method = 'post';

	/**
	 * This is just an internal tracker for generating the form html to see a submit button has been added, otherwise
	 * it makes sure to add one at the end of the form.
	 *
	 * @var bool
	 */
	private $submitButton = false;

	/**
	 * This is a form setting to enable or disable cross site request forgery protection. This should be disabled for
	 * non-session aware scripts (such as when the system is run with the REST or CLI iohandler).
	 *
	 * @var bool
	 */
	static protected $xsfrProtection = true;

	/**
	 * This is an internal flag to see if a form has been submitted. It is set by the checkSubmit function.
	 *
	 * @var bool
	 */
	protected $wasSubmitted = false;

	/**
	 * There is some basic cache handling on the form html generation, as that code can take a lot of time for more
	 * comprehensive forms. This still needs work, so its disabled by default.
	 *
	 * @var bool
	 */
	protected $cacheEnabled = false;

	/**
	 * This is an array of keys used for caching
	 *
	 * @var array
	 */
	protected $cacheKey = array();
	// These types of inputs do not get the user input resent out

	/**
	 * These input types do not get their values changed to the user input in the event of the form being resent out.
	 *
	 * @var array
	 */
	protected $discardInput = array('password', 'hidden');

	/**
	 * This takes in the name of the form and runs the 'define' method, if it exists (this gives a way for subclasses
	 * to easily set up form inputs that exist by default).
	 *
	 * @param string $name
	 */
	public function __construct($name)
	{
		$this->name = $name;
		if(method_exists($this, 'define'))
			$this->define();
	}

	/**
	 * This creates a new input under the currently active section.
	 *
	 * @param string $name
	 * @return FormInput
	 */
	public function createInput($name)
	{
		$input = new FormInput($name);
		$input->attachToForm($this);
		return $input;
	}

	/**
	 * This changes the section for adding new inputs, legends and section text. It returns the form to allow method
	 * chaining.
	 *
	 * @param string $name
	 * @return Form
	 */
	public function changeSection($name)
	{
		$this->activeSection = $name;
		return $this;
	}

	/**
	 * This function is primarily used for merging forms, but can be used to independently create an input object
	 * and attach it to the form.
	 *
	 * @param FormInput $input
	 * @return bool
	 */
	public function attachInput(FormInput $input)
	{
		$this->inputs[$this->activeSection][] = $input;
		return true;
	}

	/**
	 * This fuction sets the action url for html forms. Returns the Form object for method chaining.
	 *
	 * @param Url|string $action
	 * @return Form
	 */
	public function setAction($action)
	{
		if($action instanceof Url)
			$action = (string) $action;

		$this->action = $action;
		return $this;
	}

	/**
	 * This function sets the method, post or get, of the form. It returns the Form object form method chaining.
	 *
	 * @param string $method Port or Get
	 * @return Form
	 */
	public function setMethod($method)
	{
		if(in_array($method, array('post', 'get')))
			$this->method = $method;

		return $this;
	}

	/**
	 * This function sets the legend text of the current section. It returns the Form object form method chaining.
	 *
	 * @param string $text
	 * @return Form
	 */
	public function setLegend($text)
	{
		$this->sectionLegends[$this->activeSection] = $text;
		return $this;
	}

	/**
	 * This sets the section introduction text. It returns the Form object form method chaining.
	 *
	 * @param string $text
	 * @return Form
	 */
	public function setSectionIntro($text)
	{
		$this->sectionIntro[$this->activeSection] = $text;
		return $this;
	}

	/**
	 * This enables caching and sets an array for the cacheKey. Caching is still being tested, its not ready for
	 * production.
	 *
	 * @param array|string|false $cacheKey
	 */
	public function enableCache($cacheKey = false)
	{
		$this->cacheEnabled = true;

		if(is_array($cacheKey))
		{
			$this->cacheKey = $cacheKey;
		}elseif(is_string($cacheKey)){
			$this->cacheKey = array($cacheKey);
		}

	}

	/**
	 * This function returns the current form as html.
	 *
	 * @return string
	 */
	public function makeHtml()
	{
		$cacheKey = array_merge(array('forms', $this->name), $this->cacheKey);
		$cache = new Cache($cacheKey);

		$formHtml = $cache->getData();
		if($cache->isStale() || !$this->cacheEnabled)
		{
			if(self::$xsfrProtection && $nonce = $this->getNonce())
			{
				$this->createInput('nonce')->
					setType('hidden')->
					property('value', $this->getNonce());
			}

			// if the action isn't set, use the current link
			if(!isset($this->action))
			{
				$this->setAction(Query::getUrl());
			}

			$formHtml = new HtmlObject('form');
			$formHtml->property('method', $this->method)->
						property('id', $this->name)->
						property('action', $this->action);

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
					$input->property('id', $this->name . "_" . $input->name);
					$inputJavascript = $this->getInputJavascript($input);

					if(is_array($inputJavascript['startup']))
						$jsStartup = array_merge_recursive($jsStartup, $inputJavascript['startup']);

					if(is_array($inputJavascript['includes']))
						$jsIncludes = array_merge_recursive($jsIncludes, $inputJavascript['includes']);

					$this->processSpecialInputFields($input);
					$inputHtml = $this->getInputHtmlByType($input);

					if($input->type == 'hidden')
					{
						$inputHtml->noClose();
						$formHtml->wrapAround($inputHtml);
					}else{

						$labelHtml = new HtmlObject('label');
						$labelHtml->property('for', $input->property('id'))->
							property('id', $input->property('id') . '_label');

						if(isset($input->label))
						{
							$labelHtml->wrapAround($input->label);
						}

						$br = new HtmlObject('br');
						$br->noClose();

						$labelHtml->property('for', $input->property('id'))->
							property('id', $input->property('id') . '_label');

						$sectionHtml->wrapAround($labelHtml)->
							wrapAround($inputHtml)->
							wrapAround($br);

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
			$cache->storeData($formHtml);
		}

		$output = $formHtml;

		$jsStartup[] = '$(\'#' . $this->name . '\').validate();';

		// if the form was submitted, trigger the errors on reload
		if($this->wasSubmitted())
			$jsStartup[] = '$(\'#' . $this->name . '\').valid();';

		$jqueryPlugins = array('form', 'validate', 'validate-methods', 'cluetip', 'FCKeditor');

		if(class_exists('ActivePage', false))
		{
			$page = ActivePage::getInstance();
			$page->addStartupScript($jsStartup);
			$page->addJavaScript($jqueryPlugins, 'jquery');

			foreach ($jsIncludes as $library => $plugin)
			{
				$page->addJavaScript($plugin, $library);
			}
			$page->addCss($this->name, 'forms');
			//$page->

		}else{

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
				$inputHtml->tightEnclose();
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
			$validationClasses = json_encode(array('validation' => $validationRules));
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

	/**
	 * This function checks to see if the form was submitted or not.
	 *
	 * @return bool
	 */
	public function wasSubmitted()
	{
		$inputHandler = $this->getInputhandler();
		return (count($inputHandler) > 0);
	}

	/**
	 * This function returns the current user inputs corresponding to the output.
	 *
	 * @return array
	 */
	public function getInputhandler()
	{
		if(!isset($this->userInput))
			$this->userInput = Input::getInput();

		return $this->userInput;
	}

	/**
	 * This function checks to ensure the form was not just submitted but submitted properly. This function checks
	 * validation rules and security features, and in the event of failure sets the user inputs as the values so the
	 * form doesn't need to be completely filled out again.
	 *
	 * @return bool|array Returns either false (if the form doesn't validate) or an array of processed inputs.
	 */
	public function checkSubmit()
	{
		try
		{
			$inputHandler = $this->getInputhandler();
			$processedInput = array();

			if(!$this->wasSubmitted())
				return false;

			$success = true;

			foreach($this->inputs as $section => $inputs)
				foreach($inputs as $input)
			{
				$validationResults = $input->validate();
				if($input->validate(isset($inputHandler[$input->name]) ? $inputHandler[$input->name] : null) !== true)
				{
					$success = false;
					//$error[$input->name] = $input->getErrors;
				}

				// Is the input allows it, place the user value in as the default this way if the form isn't
				// validated, the user doesn't need to re-enter it
				switch ($input->type)
				{
					case 'hidden':
						if($input->name == 'nonce')
							continue;

					case 'password':
						$processedInput[$input->name] = $input->filter($inputHandler[$input->name]);
						continue;

					case 'checkbox':
						if(isset($inputHandler[$input->name]))
						{
							$checkboxInputs = $this->getInput($input->name);
							if(count($checkboxInputs) == 1)
							{
								$inputHandler[$input->name] = $inputHandler[$input->name][0];

								if($inputHandler[$input->name] == 'on')
								{
									$inputHandler[$input->name] = true;
								}

								$processedInput[$input->name] = $input->filter($inputHandler[$input->name]);

								$input->check(true);
							}else{
								if(in_array($input->property('value'), $inputHandler[$input->name]))
								{
									$input->check(true);
									$processedInput[$input->name][] = $input->filter($inputHandler[$input->name]);
								}else{
									$input->check(false);
								}
							}
						}else{
							$input->check(false);
						}
						break;

					default:
						$input->property('value', $inputHandler[$input->name]);
						$processedInput[$input->name] = $input->filter($inputHandler[$input->name]);
				} // switch ($input->type)

			} // foreach($inputs as $input) / foreach($this->inputs as $section => $inputs)

			// we place this here on the off chance someone is submitting a stale form, so things get filled again
			if(self::$xsfrProtection && $inputHandler['nonce'] != $this->getNonce())
			{
				throw new BentoWarning('Potential XSFR attack blocked');
			}

		}catch(Exception $e){
			$success = false;
		}

		if(!$success)
			return false;

		return $processedInput;
	}

	/**
	 * This returns a nonce, which is a token unique to the user.
	 *
	 * @return unknown
	 */
	protected function getNonce()
	{
		if(self::$xsfrProtection && isset($_SESSION['none']))
		{
			return md5($_SESSION['nonce'] . $this->name);
		}

		return false;
	}

	/**
	 * This function disables cross site forgery protection for all uses of this class.
	 *
	 */
	static public function disableXsfrProtection()
	{
		self::$xsfrProtection = false;
	}

	/**
	 * This merges another forms sections and inputs into this form.
	 *
	 * @param Form $form
	 */
	public function merge($form)
	{
		$package = $form->getMergePackage();

		$this->inputs = array_merge_recursive($this->inputs, $package['inputs']);
		$this->sectionIntro = array_merge_recursive($this->sectionIntro, $package['intros']);
		$this->sectionLegends = array_merge_recursive($this->sectionLegends, $package['legends']);
	}

	/**
	 * This returns an array for other packages to use when merging.
	 *
	 * @return array
	 */
	public function getMergePackage()
	{
		$package['inputs'] = $this->inputs;
		$package['intros'] = $this->sectionIntro;
		$package['legends'] = $this->sectionLegends;
		return $package;
	}

	/**
	 * This returns an array of input names.
	 *
	 * @return array
	 */
	public function getInputList()
	{
		$inputList = array();
		foreach($this->inputs as $section)
		{
			foreach($section as $input)
			{
				$inputList[] = $input->name;
			}
		}
		return $inputList;
	}

	/**
	 * This returns a FormInput from the list of stored inputs. The second argument, section, is optional but makes
	 * things much faster. If multiple inputs match (common with checkboxes) they are all returned.
	 *
	 * @param string $name
	 * @param string $section
	 * @return FormInput|array
	 */
	public function getInput($name, $section = null)
	{
		$inputList = !isset($section) ? $this->inputs : array($this->inputs[$section]);
		$matchedInputs = array();
		foreach($inputList as $inputs)
		{
			if(is_array($this->inputs))
			{
				foreach($inputs as $input)
				{
					if($input->name == $name)
						$matchedInputs[] = $input;
				}
			}elseif($inputs instanceof FormInput && $inputs->name == $name){
				$matchedInputs[] = $inputs;
			}
		}

		switch(count($matchedInputs))
		{
			case 0:
				return false;
			case 1:
				return array_pop($matchedInputs);
			default:
				return $matchedInputs;
		}
	}
}

?>