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
	 * This is an array of followup text for each section.
	 *
	 * @var array
	 */
	protected $sectionOutro = array();

	/**
	 * This is an array of classes added to the fieldset tag for a given section.
	 *
	 * @var array
	 */
	protected $sectionClasses = array();

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
	static public $xsfrProtection = true;

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
	 * Contains an associative array with the errors from each input that has undergone validation.
	 *
	 * @var array
	 */
	protected $errors;

	/**
	 * The string identifier for the markup handler used to process the contents of any "richtext" inputs.
	 *
	 * @var string
	 */
	protected $richtextFormat = 'html';

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
	 * This fuction sets the markup format used for richtext fields. Returns the Form object for method chaining.
	 *
	 * @param string $action
	 * @return Form
	 */
	public function setMarkup($markup)
	{
		$this->richtextFormat = $markup;
		return $this;
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
	 * This sets the section followup text. It returns the Form object form method chaining.
	 *
	 * @param string $text
	 * @return Form
	 */
	public function setSectionOutro($text)
	{
		$this->sectionOutro[$this->activeSection] = $text;
		return $this;
	}

	/**
	 * This adds a class to the list for a given section fieldset. It returns the Form object form method chaining.
	 *
	 * @param string $class
	 * @return Form
	 */
	public function addSectionClass($class)
	{
		$this->sectionClasses[$this->activeSection][] = $class;
		return $this;
	}


	/**
	 * Returns the currently-set markup format for "richtext" inputs.
	 *
	 * @return string
	 */
	public function getMarkup()
	{
		return $this->richtextFormat;
	}

	/**
	 * This function returns the action url the form should be submitted to, defaulting to 'self'  if no action has been
	 * set(as in, submit back to the action calling the form).
	 *
	 * @return string
	 */
	public function getAction()
	{
		if(!isset($this->action))
		{
			$this->setAction(Query::getUrl());
		}

		return $this->action;
	}

	/**
	 * This function returns the method used to submit the form, most commonly 'Post'.
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
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
	 * This function returns the form in the requested format. It does so by calling upon a 'converter' class, located
	 * in Form/Converters
	 *
	 * @param string $format
	 * @return string
	 */
	public function getFormAs($format = 'Html')
	{
		if(self::$xsfrProtection
			&& !$this->getInput('nonce')
			&& $nonce = $this->getNonce())
		{
			$this->createInput('nonce')->
				setType('hidden')->
				property('value', $nonce);
		}

		$converterClass = 'FormTo' . $format;

		if(!class_exists($converterClass))
			throw new FormError('Unable to load conversation class ' . $converterClass);

		$converter = new $converterClass($this);
		return $converter->makeOutput();
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
	 * @hook Forms checkSubmit Base
	 * @hook Forms checkSubmit *type
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
			$processedCheckboxes = array();
			foreach($this->inputs as $section => $inputs)
				foreach($inputs as $input)
			{
				$plugins = new Hook();
				//$plugins->enforceInterface('FormToHtmlHook');
				$plugins->loadPlugins('Forms', 'checkSubmit', 'Base');
				$plugins->loadPlugins('Forms', 'checkSubmit', $input->type);

				if($input->type == 'richtext')
					$input->property('format', $this->richtextFormat);

				$plugins->setInput($input);
				$plugins->processInput($inputHandler);

				if($input->validate(isset($inputHandler[$input->name]) ? $inputHandler[$input->name] : null) !== true)
				{
					$success = false;
					$error[$input->name] = $input->validationMessages;
				}

				// Is the input allows it, place the user value in as the default this way if the form isn't
				// validated, the user doesn't need to re-enter it
				switch ($input->type)
				{
					case 'hidden':
						if($input->name == 'nonce')
							continue;

					case 'password':

						if(isset($inputHandler[$input->name]) && strlen($inputHandler[$input->name]) > 0)
						{
							$processedInput[$input->name] = $input->filter($inputHandler[$input->name]);
						}elseif($value = $input->property('value')){
							$processedInput[$input->name] = $value;
						}
						continue;

					case 'checkbox':

						if(in_array($input->name, $processedCheckboxes))
							continue;

						$processedCheckboxes[] = $input->name;

						$checkboxInputs = $this->getInput($input->name);
						$checkboxValues = (isset($inputHandler[$input->name]))
												? $inputHandler[$input->name]->getArrayCopy() : false;

						if($checkboxInputs instanceof FormInput)
						{
							$checkboxInputs->check(false);
							$processedInput[$input->name] = false;
							if($checkboxValues)
							{
								if(count($checkboxValues) == 1)
								{
									$inputValue = array_pop($checkboxValues);
									$processedInput[$input->name] = ($inputValue == 'on') ? true : $inputValue;
									$checkboxInputs->check(true);
									unset($inputValue);
								}
							}

						}elseif(is_array($checkboxInputs) && count($checkboxInputs) > 0){

							foreach($checkboxInputs as $checkbox)
							{
								$checkbox->check(false);

								if(!$checkboxValues)
									continue;

								if(isset($checkbox->properties['value']) && (in_array($checkbox->properties['value'], $checkboxValues)))
								{
									$processedInput[$input->name][] = $checkbox->properties['value'];
									$checkbox->check(true);
								}
							}
						}else{
							throw new FormNotice('Unable to find checkboxes, even though one was found. This shouldn\'t
												happen, but if you see this it obviously did. I\'m sorry.');
						}

						break;

					default:
						$input->property('value', $inputHandler[$input->name]);
						$processedInput[$input->name] = $input->filter($inputHandler[$input->name]);
				} // switch ($input->type)

			} // foreach($inputs as $input) / foreach($this->inputs as $section => $inputs)

			if(isset($error))
				$this->errors = $error;

			// we place this here on the off chance someone is submitting a stale form, so things get filled again
			if(self::$xsfrProtection && $inputHandler['nonce'] != $this->getNonce())
			{
				throw new FormWarning('Potential XSFR attack blocked');
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
	public function getNonce()
	{
		if(self::$xsfrProtection && isset($_SESSION['nonce']))
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
		$this->sectionOutro = array_merge_recursive($this->sectionIntro, $package['outros']);
		$this->sectionLegends = array_merge_recursive($this->sectionLegends, $package['legends']);
	}

	/**
	 * This function returns the name of the form.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * This returns an array for other packages to use when merging.
	 *
	 * @return array inputs, intros, legends
	 */
	public function getMergePackage()
	{
		$package['inputs'] = $this->inputs;
		$package['intros'] = $this->sectionIntro;
		$package['outros'] = $this->sectionOutro;
		$package['legends'] = $this->sectionLegends;
		$package['classes'] = $this->sectionClasses;
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
	 * This removes a FormInput from the form completely. The second argument, section, is optional but makes
	 * things much faster. If multiple inputs match (common with checkboxes) they are all returned.
	 *
	 * @param string $name
	 * @param string $section = null
	 * @return bool
	 */
	public function removeInput($name, $section = null)
	{
		$deleted = false;

		foreach($this->inputs as $sectionName => $sectionData) {
			if(isset($section) && $section != $sectionName) {
				continue;
			}

			$sectionNew = array();
			foreach($sectionData as $input) {
				if($input->name != $name) {
					$sectionNew[] = $input;
				} else {
					$deleted = true;
				}
			}

			$this->inputs[$sectionName] = $sectionNew;
		}

		return $deleted;
	}

	/**
	 * This returns a FormInput from the list of stored inputs. The second argument, section, is optional but makes
	 * things much faster. If multiple inputs match (common with checkboxes) they are all returned.
	 *
	 * @param string $name
	 * @param string $section = null
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

class FormWarning extends CoreWarning {}
class FormNotice extends CoreNotice {}
class FormError extends CoreError {}
class FormValidation extends FormNotice {}
?>