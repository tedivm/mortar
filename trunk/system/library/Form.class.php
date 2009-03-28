<?php
require('Form/Input.class.php');


class Form
{
	public $name;
	static public $userInput = false;

	protected $activeSection = 'main';
	protected $inputs = array();
	protected $action = false;
	protected $sectionLegends = array();
	protected $sectionIntro = array();

	//protected $handlerClass = 'FormHandlerHtml';
	protected $method = 'post';
	protected $submitButton = false;

	static protected $xsfrProtection = true;
	protected $wasSubmitted = false;
	protected $error = array();
	protected $cacheEnabled = false;
	protected $cacheKey = array();
	// These types of inputs do not get the user input resent out
	protected $discardInput = array('password', 'hidden');

	public function __construct($name)
	{
		$this->name = $name;
		if(method_exists($this, 'define'))
			$this->define();
	}

	public function createInput($name)
	{
		$input = new FormInput($name);
		$input->attachToForm($this);
		return $input;
	}

	public function changeSection($name)
	{
		$this->activeSection = $name;
		return $this;
	}

	public function attachInput($input)
	{
		$this->inputs[$this->activeSection][] = $input;
		return true;
	}

	public function setAction($action)
	{
		$this->action = $action;
		return $this;
	}

	public function setMethod($method)
	{
		if(in_array($method, array('post', 'get')))
			$this->method = $method;

		return $this;
	}

	public function setLegend($text)
	{
		$this->sectionLegends[$this->activeSection] = $text;
		return $this;
	}

	public function setSectionIntro($text)
	{
		$this->sectionIntro[$this->activeSection] = $text;
		return $this;
	}

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

	public function makeDisplay($resource = null)
	{
		// $resource isn't used yet


		$cacheKey = array_merge(array('forms', $this->name), $this->cacheKey);
		$cache = new Cache($cacheKey);

		$formHtml = $cache->getData();

		if(!$cache->cacheReturned || !$this->cacheEnabled)
		{
			if(self::$xsfrProtection)
			{
				$nonce = $this->getNonce();
				if($nonce != 0)
				{
					$this->createInput('nonce')->
						setType('hidden')->
						property('value', $this->getNonce());
				}
			}

			$formHtml = new HtmlObject('form');
			$formHtml->property('method', $this->method)->
						property('id', $this->name)->
						property('action', (($this->action) ? $this->action : $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']) );

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


				$jsIncludes = array();
				$jsStartup = array();

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

		//$jsStartup[] = '$(\'#' . $this->name . '\').validate();';

		// if the form was submitted, trigger the errors on reload
		if($this->wasSubmitted())
			$jsStartup[] = '$(\'#' . $this->name . '\').valid();';

		$jqueryPlugins = array('form', 'validate', 'validate-methods', 'cluetip', 'FCKeditor');

		if(class_exists('ActivePage', false))
		{
			$page = ActivePage::getInstance();
			$page->addStartupScript($jsStartup);
			$page->addJQueryInclude($jqueryPlugins);
			$page->addJavaScript($jsIncludes);
			$page->addCss($this->name, 'forms');
			//$page->

		}else{

		}

		return $output;
	}


	protected function processSpecialInputFields(FormInput $input)
	{

		// preprocess special types
		switch ($input->type) {
			case 'location':
				$input->type = 'select';
				// check property for base location
				// check for array of location types
				if(is_array($input->property('types')))
				{
					$types = $input->property('types');
					unset($input->properties['types']);
				}elseif(is_string($input->property('types'))){
					$types = $input->property('types');
				}else{
					$types = '';
				}

				if(is_numeric($input->property('baseLocation')))
				{
					$locationId = $input->property('baseLocation');
					unset($input->properties['baseLocation']);
				}else{
					$locationId = 1; // If no location is set, default toroot
				}

				if(is_numeric($input->property('value')))
				{
					$value = $input->property('value');
					unset($input->properties['value']);
				}else{
					$value = 1;
				}

				$baseLocation = new Location($locationId);
				$locationList = $baseLocation->getTreeArray($types);

				foreach($locationList as $id => $string)
				{
					$attributes = array();
					if($value == $id)
						$attributes = array('selected' => 'selected');
					$input->setOptions($id, $string, $attributes);
				}
				break;

			case 'module':
				if(!isset($input->properties['moduleName']))
					throw new BentoError('Module type required for input type module.');

				$packageInfo = new PackageInfo($input->properties['moduleName']);
				$permission = ($input->properties['permission']) ? $input->properties['permission'] : '';
				$moduleList = $packageInfo->getModules($permission);
				$input->type = 'select';

				foreach($moduleList as $module)
				{
					$location = new Location($module['locationId']);
					$input->setOptions($module['modId'], (string) $location);
				}
				break;

			default:
				break;
		}

	}

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

		if(!is_null($validationRules))
		{
			$validationClasses = json_encode(array('validation' => $validationRules));
			$inputHtml->addClass($validationClasses);
		}


		return $inputHtml;

	}

	protected function getInputJavascript(FormInput $input)
	{

		// to require a javascript file, return $include['Library'][] = 'Name';
		$includes = $startup = $plugin = array();

		switch ($input->type) {
			case 'html':
				if(is_array($input->property('options')))
					$fckOptions = json_encode($input->property('options'));

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


	public function wasSubmitted()
	{
		$inputHandler = $this->getInputhandler();
		return (count($inputHandler) > 0);
	}

	public function getInputhandler()
	{
		if(isset(self::$userInput))
			self::$userInput = Input::getInput();

		return self::$userInput;
	}

	public function checkSubmit()
	{
		try
		{
			$inputHandler = $this->getInputhandler();

			if(!$this->wasSubmitted())
				return false;

			$success = true;


			foreach($this->inputs as $section => $inputs)
				foreach($inputs as $input)
			{

				$validationResults = $input->validate();

				if($input->validate() !== true)
				{
					$success = false;
					//$error[$input->name] = $input->getErrors;
				}
				// Is the input allows it, place the user value in as the default this way if the form isn't
				// validated, the user doesn't need to re-enter it
				if(in_array($input->type, $this->discardInput))
					continue;


				switch ($input->type)
				{
					case 'checkbox':
						if(isset($inputHandler[$input->name]))
						{
							$checkboxInputs = $this->getInput($input->name);
							if(count($checkboxInputs) == 1)
							{
								$inputHandler[$input->name] = $inputHandler[$input->name][0];
								if($inputHandler[$input->name] == 'on')
									$inputHandler[$input->name] = true;
								$input->check(true);
							}else{
								$input->check(in_array($input->property('value'), $inputHandler[$input->name]));
							}
						}else{
							$input->check(false);
						}
						break;

					default:
						$input->property('value', $inputHandler[$input->name]);
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

		return $success;
	}

	protected function getNonce()
	{
		if(!self::$xsfrProtection &&class_exists('ActiveUser', false))
		{
			$activeUser = ActiveUser::get_instance();
			$output = $activeUser->session('nonce');
			return $output;
		}

		return '0';
	}

	static public function disableXsfrProtection()
	{
		self::$xsfrProtection = false;
	}

	public function merge($form)
	{
		$package = $form->getMergePackage();

		$this->inputs = array_merge_recursive($this->inputs, $package['inputs']);
		$this->sectionIntro = array_merge_recursive($this->sectionIntro, $package['intros']);
		$this->sectionLegends = array_merge_recursive($this->sectionLegends, $package['legends']);
	}

	public function getMergePackage()
	{
		$package['inputs'] = $this->inputs;
		$package['intros'] = $this->sectionIntro;
		$package['legends'] = $this->sectionLegends;
		return $package;
	}

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

	public function getInput($name, $section = false)
	{
		$inputList = (!$section) ? $this->inputs : array($this->inputs[$section]);
		$matchedInputs = array();
		foreach($inputList as $inputs)
		{
			foreach($inputs as $input)
			{
				if($input->name == $name)
					$matchedInputs[] = $input;
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